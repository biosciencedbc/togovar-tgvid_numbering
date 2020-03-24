FROM tenforce/virtuoso


WORKDIR /

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y tzdata \
    && apt-get clean

ENV TZ=Asia/Tokyo

RUN apt-get install -y wget \
    && apt-get install -y gcc \
    && apt-get install -y make \
    && apt-get install -y libz-dev \
    && apt-get install -y libncurses5-dev \
    && apt-get install -y libbz2-dev \
    && apt-get install -y liblzma-dev \
    && apt-get clean

RUN wget https://github.com/samtools/samtools/releases/download/1.9/samtools-1.9.tar.bz2 \
    && tar -jxvf samtools-1.9.tar.bz2 \
    && cd samtools-1.9/ \
    && ./configure --prefix=/usr/local/ \
    && make -j 8 \
    && make install

RUN apt-get install -y virtuoso-opensource

RUN mkdir /root/numbering_tgvid

COPY ./copy/ /
