#!/bin/sh
set -Ceu

# load config.txt
. /root/numbering_tgvid/input/config/config.txt &&
echo "loaded setting file : config.txt"

# make temporary directory
if [ -d ${tmp_dir} ]; then
  if [ -d ${tmp_dir}.bk ]; then
      	ls -1dr ${tmp_dir}.bk* | xargs rm -r
  fi
  mv -fT ${tmp_dir} ${tmp_dir}.bk
fi

mkdir ${tmp_dir}
chmod 777 ${tmp_dir}
echo "make temporary directory"

# compress vcf files
gzip ${input_dir}/vcf/*.vcf

# normalize vcf
for vcf_gz in ${input_dir}/vcf/*.vcf.gz  ; do
  ${bcftools_exec} norm -m - -O z --threads 32 -f ${input_dir}/fasta/*.fa ${vcf_gz} |\
  ${bcftools_exec} annotate --set-id '%CHROM\_%POS\_%REF\_%ALT' --threads 32 |\
  ${bcftools_exec} query -f '%ID\t%CHROM\t%POS\t%REF\t%ALT\n' |\
  >> ${tmp_dir}/bftool_out
done

# split tsv
cat ${tmp_dir}/bftool_out | LC_ALL=C sort | uniq | split -d -a 4 -l 80000 - ${tmp_dir}/split_out

# add header
for split_file in ${tmp_dir}/split_out* ; do
  cat ${input_dir}/config/numbering_header.txt ${split_file} > ${split_file}.tsv
done



# Convert to RDF(ttl)file and load to virtuoso
sh /root/numbering_tgvid/script/02_exeImportNewData.sh ${tmp_dir}


# output tgvid list
php /root/numbering_tgvid/script/10_exportAll.php
