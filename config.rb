# Compass configuration file for Moodle
# 
# Usage:
# 
# For a one-off build (default debug output)
# 
#   $ compass compile
# 
# Or for automated builds that watch project files
# 
#   $ compass watch
# 
# For production environments with compressed stylesheets
# 
#   $ compass compile -e production --force 

require 'sass-css-importer'
add_import_path Sass::CssImporter::Importer.new("theme/bootstrapbase/style")


## UCLA theme directory
theme_dir = "theme/uclashared"

## Location of CSS dir
css_dir = "#{theme_dir}/style"

## Location of SASS dir
sass_dir = "#{theme_dir}/sass"

## Location of images
images_dir = "#{theme_dir}/pix"

## Location of javascript
javascripts_dir = "#{theme_dir}/javascript"

## Import boostrap package
additional_import_paths = ["#{theme_dir}/package", sass_dir]

## For production environment, use compressed CSS
output_style = (environment == :production) ? :compressed : :expanded


# Set up compass 'watch' for Moodle modules
# 
# When you run 'compass watch', it will listen for changes to a 'styles.scss' file in 
# the 'sass' folder of any Moodle module and compile it to a 'styles.css' file 
# that Moodle automatically loads.
# 
# This scheme also gives module sass access to all the mixins defined for the project theme, 
# so that it's possible to require any dependency in your local module styles.scss file
# 
watch "**/sass/styles.scss" do |project_dir, relative_path|
  if File.exists?(File.join(project_dir, relative_path))
    
    ## Compile compass inside compass, whoa!
    cmd = "compass compile --sass-dir #{relative_path.gsub(/\/styles.scss/, '')} --css-dir #{relative_path.gsub(/\/sass\/styles.scss/, '')} -I #{sass_dir}"
    puts cmd
    
    ## Do system call
    system(cmd)

    ## This is another way of doing it in pure ruby, but not possible with watch...
    ## Save for reference
    # Compass.add_configuration(
    #     {
    #         :project_path => '.',
    #         :sass_path => "#{relative_path.gsub(/\/styles.scss/, '')}",
    #         :css_path => "#{relative_path.gsub(/\/sass\/styles.scss/, '')}"
    #     },
    #     'moodle-modules'
    # )
    # Compass.compiler.compile('styles.scss', 'style2.css')
  end
end


# To enable relative paths to assets via compass helper functions. Uncomment:
# relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
# line_comments = false

