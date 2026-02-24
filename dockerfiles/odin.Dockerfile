# install odin
RUN wget 'https://github.com/odin-lang/Odin/releases/download/dev-2026-02/odin-linux-amd64-dev-2026-02.tar.gz' -O /home/builduser/odin.tar.gz

RUN mkdir -p /home/builduser/odin && tar -xzf /home/builduser/odin.tar.gz -C /home/builduser/odin

ENV PATH="/home/builduser/odin/odin-linux-amd64-nightly+2026-02-04/:${PATH}"
