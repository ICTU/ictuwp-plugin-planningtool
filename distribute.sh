

# sh '/Users/paul/shared-paul-files/Webs/git-repos/Digitale-Overheid---WordPress-plugin-Planning-Tool/distribute.sh' &>/dev/null

# voor een update van de CMB2 bestanden:
# sh '/Users/paul/shared-paul-files/Webs/git-repos/ICTU---Digitale-Overheid-WP---rijksvideoplugin/get_cmb2_files.sh' &>/dev/null


# cp '/Volumes/Macintosh HD/shared-paul-files/Webs/git-repos/ICTU---Digitale-Overheid-WP-theme/less/00-palet.less' '/Users/paul/shared-paul-files/Webs/git-repos/Digitale-Overheid---WordPress-plugin-Planning-Tool/less/palet-do.less';

echo '----------------------------------------------------------------';
echo 'Distribute DO planning tool plugin';

# clear the log file
> '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/development/wp-content/debug.log'

# copy to temp dir
rsync -r -a --delete '/Users/paul/shared-paul-files/Webs/git-repos/Digitale-Overheid---WordPress-plugin-Planning-Tool/' '/Users/paul/shared-paul-files/Webs/temp/'

# clean up temp dir
rm -rf '/Users/paul/shared-paul-files/Webs/temp/.git/'
rm '/Users/paul/shared-paul-files/Webs/temp/.gitignore'
rm '/Users/paul/shared-paul-files/Webs/temp/config.codekit3'
rm '/Users/paul/shared-paul-files/Webs/temp/distribute.sh'
rm '/Users/paul/shared-paul-files/Webs/temp/README.md'
rm '/Users/paul/shared-paul-files/Webs/temp/LICENSE'
rm '/Users/paul/shared-paul-files/Webs/temp/wijzigingen-planningstool.txt'



# clean up temp dir
rm -rf '/Users/paul/shared-paul-files/Webs/temp/.git/'
rm '/Users/paul/shared-paul-files/Webs/temp/.gitignore'
rm '/Users/paul/shared-paul-files/Webs/temp/config.codekit3'
rm '/Users/paul/shared-paul-files/Webs/temp/.config.codekit3'
rm '/Users/paul/shared-paul-files/Webs/temp/distribute.sh'
rm '/Users/paul/shared-paul-files/Webs/temp/README.md'
rm '/Users/paul/shared-paul-files/Webs/temp/LICENSE'



# --------------------------------------------------------------------------------------------------------------------------------
# Vertalingen --------------------------------------------------------------------------------------------------------------------
# --------------------------------------------------------------------------------------------------------------------------------
# remove the .pot
rm '/Users/paul/shared-paul-files/Webs/temp/languages/do-planning-tool.pot'

# copy files to /wp-content/languages/themes
rsync -ah '/Users/paul/shared-paul-files/Webs/temp/languages/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/development/wp-content/languages/plugins/'

# languages Sentia accept
rsync -ah '/Users/paul/shared-paul-files/Webs/temp/languages/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/sentia/accept/www/wp-content/languages/plugins/'

# languages Sentia live
rsync -ah '/Users/paul/shared-paul-files/Webs/temp/languages/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/sentia/live/www/wp-content/languages/plugins/'


cd '/Users/paul/shared-paul-files/Webs/temp/'
find . -name ‘*.DS_Store’ -type f -delete


# --------------------------------------------------------------------------------------------------------------------------------


# copy from temp dir to dev-env
rsync -r -a --delete '/Users/paul/shared-paul-files/Webs/temp/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/development/wp-content/plugins/ictuwp-plugin-planningtool/' 

# remove temp dir
rm -rf '/Users/paul/shared-paul-files/Webs/temp/'
rm -rf '/Users/paul/shared-paul-files/Webs/temp-languages/'

# een kopietje naar Sentia accept
rsync -r -a --delete '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/development/wp-content/plugins/ictuwp-plugin-planningtool/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/sentia/accept/www/wp-content/plugins/ictuwp-plugin-planningtool/'

# en een kopietje naar Sentia live
rsync -r -a --delete '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/development/wp-content/plugins/ictuwp-plugin-planningtool/' '/Users/paul/shared-paul-files/Webs/ICTU/Gebruiker Centraal/sentia/live/www/wp-content/plugins/ictuwp-plugin-planningtool/'



echo 'Ready';
echo '----------------------------------------------------------------';
