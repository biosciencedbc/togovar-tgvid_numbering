# togovar-tgvid_numbering

tgvid numbering and output of vcf files

## Installation

```
$ docker build --tag togovar-tgvid_numbering .
$ docker run -itd --name togovar-tgvid_numbering -v $(pwd)/numbering/:/root/numbering_tgvid/ togovar-tgvid_numbering
```

## Excution

- start virtuoso
```
$ docker exec togovar-tgvid_numbering sh start_virtuoso.sh
```
- delete and load ttl data
place ttl files in "$(pwd)/numbering/input/load/"
```
$ docker exec togovar-tgvid_numbering sh all_delete_ad_load.sh
```

- tgvid numbering
place vcf files in "$(pwd)/numbering/input/vcf/"
```
$ docker exec togovar-tgvid_numbering sh numbering.sh
```
tsv files is output to "$(pwd)/numbering/output/"

- only output tsv files
```
$ docker exec togovar-tgvid_numbering sh output_tgvid.sh
```
tsv files is output to "$(pwd)/numbering/output"

if you want to specify offset, you can specify 100,000 units as an argument.
example)
```
$ docker exec togovar-tgvid_numbering sh output_tgvid.sh 100000
```
