# install alire
RUN wget https://github.com/alire-project/alire/releases/download/v2.1.0/alr-2.1.0-bin-x86_64-linux.zip -O /home/builduser/alr.zip && \
    unzip /home/builduser/alr.zip -d /home/builduser/alr_dist && \
    mv /home/builduser/alr_dist/bin/alr /usr/local/bin/alr && \
    chmod +x /usr/local/bin/alr

# install toolchain
RUN alr -n toolchain --select gnat_native gprbuild

# precompile
RUN git clone https://github.com/jinyus/related_post_gen.git /tmp/repo && \
    cd /tmp/repo/ada && \
    alr -n build --release
