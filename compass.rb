
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
additional_import_paths = ["#{theme_dir}/package"]


# @todo 
# Compile CSS for Moodle modules ONLY for production
# 
# Create a styles.css files for modules
# 
Dir.glob('**/sass/styles.scss').each do |f|
   puts f
   ## Compile compass inside compass, whoa!
    cmd = "compass compile --sass-dir #{f.gsub(/\/styles.scss/, '')} --css-dir #{f.gsub(/\/styles.scss/, '')} -I #{sass_dir} --environment production --output-style compressed"
    puts cmd
    
    ## Do system call
    system(cmd)

    cmd = "mv #{f.gsub(/\/styles.scss/, '')}/styles.css #{f.gsub(/\/sass\/styles.scss/, '')}/styles.min.css"
    puts cmd
    system(cmd)
end
