<?php
declare(strict_types=1);

// PHP implementation of the "related posts" benchmark.
// Reads:  ../posts.json
// Writes: ../related_posts_php.json

const TOPN = 5;

function main(): void
{
    $postsPath = __DIR__ . '/../posts.json';
    $outPath = __DIR__ . '/../related_posts_php.json';

    $raw = file_get_contents($postsPath);
    if ($raw === false) {
        fwrite(STDERR, "Failed to read $postsPath\n");
        exit(1);
    }

    /** @var array<int, array{_id:string, title?:string, tags:array<int,string>}> $posts */
    $posts = json_decode($raw, true);

    $postsCount = count($posts);

    // tag -> list of post indices
    $tagMap = [];
    for ($i = 0; $i < $postsCount; $i++) {
        $tags = $posts[$i]['tags'];
        $tagsCount = count($tags);
        for ($j = 0; $j < $tagsCount; $j++) {
            $tag = $tags[$j];
            // Avoid slow array_merge; append directly.
            $tagMap[$tag][] = $i;
        }
    }

    $taggedPostCount = array_fill(0, $postsCount, 0);
    $start = microtime(true);
    $ioSeconds = 0.0;

    $out = fopen($outPath, 'wb');
    if ($out === false) {
        fwrite(STDERR, "Failed to open $outPath for writing\n");
        exit(1);
    }

    $t0 = microtime(true);
    fwrite($out, '[');
    $ioSeconds += microtime(true) - $t0;

    for ($i = 0; $i < $postsCount; $i++) {
        // Reset counts (faster/more predictable than array_fill in some runtimes).
        for ($j = 0; $j < $postsCount; $j++) {
            $taggedPostCount[$j] = 0;
        }

        $tags = $posts[$i]['tags'];
        $tagsCount = count($tags);
        for ($j = 0; $j < $tagsCount; $j++) {
            $otherList = $tagMap[$tags[$j]];
            $otherCount = count($otherList);
            for ($k = 0; $k < $otherCount; $k++) {
                $idx = $otherList[$k];
                $taggedPostCount[$idx] += 1;
            }
        }

        // Exclude self
        $taggedPostCount[$i] = 0;

        // Flattened list of (count, id) like python/related.py to match tie-breaking.
        $top5 = array_fill(0, TOPN * 2, 0);
        $minTags = 0;

        for ($j = 0; $j < $postsCount; $j++) {
            $count = $taggedPostCount[$j];

            if ($count > $minTags) {
                $upperBound = (TOPN - 2) * 2;

                while ($upperBound >= 0 && $count > $top5[$upperBound]) {
                    $top5[$upperBound + 2] = $top5[$upperBound];
                    $top5[$upperBound + 3] = $top5[$upperBound + 1];
                    $upperBound -= 2;
                }

                $insertPos = $upperBound + 2;
                $top5[$insertPos] = $count;
                $top5[$insertPos + 1] = $j;

                $minTags = $top5[TOPN * 2 - 2];
            }
        }

        $topPosts = [];
        for ($k = 1; $k < TOPN * 2; $k += 2) {
            $topPosts[] = $posts[$top5[$k]];
        }

        $payload = [
            '_id' => $posts[$i]['_id'],
            'tags' => $posts[$i]['tags'],
            'related' => $topPosts,
        ];

        $t0 = microtime(true);
        if ($i !== 0) {
            fwrite($out, ',');
        }

        $json = json_encode($payload);
        if ($json === false) {
            fwrite(STDERR, "Failed to encode output JSON\n");
            exit(1);
        }

        fwrite($out, $json);
        $ioSeconds += microtime(true) - $t0;
    }

    $t0 = microtime(true);
    fwrite($out, ']');
    fclose($out);
    $ioSeconds += microtime(true) - $t0;

    $elapsedMs = (int) round(((microtime(true) - $start) - $ioSeconds) * 1000.0);
    fwrite(STDOUT, "Processing time (w/o IO): {$elapsedMs}ms\n");
}

main();

