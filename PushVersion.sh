#! /bin/sh
rsync -avc --exclude-from 'PushVersion_exclude.txt' ./ ../BookMarkup/
