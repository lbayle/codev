#!/bin/bash

# attention, ca ecrase les precedents !
# TODO: adapter ./update.sh

# http://www.paperblog.fr/2356370/setup-i18n-gettext-in-your-php-application/


xgettext -kT_gettext -k_ -kT_ -d codev index.php
msgfmt -o codev.mo codev.po
