#!/bin/sh

sudo adduser kappa sudo

sudo cp -rp xdcamenc.rc /etc/init.d/xdcamenc
sudo chown kappa.kappa /etc/init.d/xdcamenc
