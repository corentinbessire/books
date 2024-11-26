
#!/bin/bash

show_help() {
    echo "Usage: ./setup.sh themeName"
    echo ""
    echo "Options:"
    echo "  themeName        The name of the Theme to create."
}

# Check if the first argument is --help
if [[ "$1" == "--help" ]]; then
    show_help
    exit 0
fi

theme_name=$1
theme_directory=$(pwd)
theme_location="$(dirname "$theme_directory")"


# ------------------------------------------------------------------------------------------------------
echo "🔗 Removing Git references..."
rm -rf $theme_directory/.git # > /dev/null 2>&1

# ------------------------------------------------------------------------------------------------------
echo "🛠️ Renaming Theme..."


#Search and Replace in Files
find . -type f -name '*.yml' -exec sed -i '' 's/tadstack/'$theme_name'/g' {} \;
find . -type f -name '*.theme' -exec sed -i '' 's/tadstack/'$theme_name'/g' {} \;
find . -type f -name '*.json' -exec sed -i '' 's/tadstack/'$theme_name'/g' {} \;
find . -type f -name '*.twig' -exec sed -i '' 's/tadstack/'$theme_name'/g' {} \;

#Renam,ing Files
find . -name "*tadstack.*" -exec rename -s 'tadstack' $theme_name {} ";" # > /dev/null 2>&1

#renaming Directory
cd $theme_location
mv $theme_directory $theme_location/$theme_name # > /dev/null 2>&1
cd $theme_location/$theme_name


