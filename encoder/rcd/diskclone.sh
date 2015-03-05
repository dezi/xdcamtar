#!/bin/sh -x

#
# Copy size 10000 = 10 GB
#

COUNT=10000

#
# Disk copy with progress...
#

dd if=/dev/mmcblk0 bs=1024k count=$COUNT | pv -s ${COUNT}m | sudo dd of=/dev/sda bs=1024k count=$COUNT iflag=fullblock

#
# Fix corrupted copy partition
#

fsck.ext4 -y /dev/sda2

#
# Remove hardware MAC address.
#

mkdir ./xxxsda2
mount /dev/sda2 ./xxxsda2

rm ./xxxsda2/etc/smsc95xx_mac_addr

umount ./xxxsda2
rmdir ./xxxsda2

#
# Create working partition.
#

mkfs.ext4 /dev/sda3

#
# Create encoding tmp directory.
#

mkdir ./xxxsda3
mount /dev/sda3 ./xxxsda3

mkdir -p ./xxxsda3/xdcamstore/encoder/tmp
chmod -R a+w ./xxxsda3/xdcamstore

umount ./xxxsda3
rmdir ./xxxsda3

