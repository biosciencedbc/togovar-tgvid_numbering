# togovar-tgvid_numbering

tgvid numbering and output of vcf files

## Installation

```
$ docker build --tag togovar-tgvid_numbering .
$ docker run -itd --name togovar-tgvid_numbering -v $(pwd)/copy/:/root/numbering_tgvid/ togovar-tgvid_numbering
```

## Excution

start virtuoso
```
$ docker exec togovar-tgvid_numbering sh start_virtuoso.sh
```


