If you want to use your own copy of MathJax to avoid calling out to mathjax.org's CDN,
you can copy the content of a MathJax package to this directory and the filter will
automatically use it. Note that the MathJax.js file should be in the same directory as
this file, along with the neighbouring directories from the MathJax distribution.

This is right:
    ~/moodle/filter/mathjax $ ls -1 js
    config
    docs
    extensions
    fonts
    jax
    LICENSE
    MathJax.js
    README.md
    README-moodle.txt
    test
    unpacked

This is wrong:
    ~/moodle/filter/mathjax $ ls -1 js
    mathjax-MathJax-f5cd294
    README-moodle.txt
