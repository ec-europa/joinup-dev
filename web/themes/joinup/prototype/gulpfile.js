// Gather used gulp plugins
var gulp = require('gulp'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    watch = require('gulp-watch'),
    sass = require('gulp-sass'),
    styleguide = require('sc5-styleguide'),
    livereload = require('gulp-livereload');
    mustache = require('gulp-mustache');


// Set paths
var paths = {
  sass: ['sass/**/*.+(scss|sass)'],
  sassStyleguide: [
    '../vendor/material-design-lite/material.css',
    'sass/**/*.+(scss|sass)',
    '!sass/_*.+(scss|sass)'
  ],
  html: ['sass/**/*.html'],
  mustache: [
    'html-prototype-sandbox/*.html',
    'html-prototype-sandbox/**/*.mustache'
  ],
  styleguide: 'styleguide',
  scripts: {
    base:       'js',
    components: 'js/components/**/*.js'
  }
};

// TODO: contat scripts and add them to styleguide
gulp.task('scripts', function(){
  return gulp.src(paths.scripts.components)
    .pipe(concat('bootsmacss.js'))
    .pipe(gulp.dest('js'));
});


// Define SASS compiling task
gulp.task('sass', function () {
  gulp.src('sass/app.sass')
    .pipe(sass(
      {outputStyle: 'compressed'}
    ).on('error', sass.logError))
    .pipe(rename('style.min.css'))
    .pipe(gulp.dest('../css'))
    .pipe(gulp.dest('css'))
    .pipe(livereload());
});


// Define Mustache compiling task
gulp.task('mustache', function() {
  return gulp.src("./html-prototype-sandbox/*.html")
    .pipe(mustache())
    .pipe(gulp.dest("./html-prototype"));
});


// Define rendering styleguide task
// https://github.com/SC5/sc5-styleguide#build-options
gulp.task('styleguide:generate', function() {
  return gulp.src(paths.sassStyleguide)
    .pipe(styleguide.generate({
        extraHead: [
          '<script src="/js/material.min.js"></script>',
          '<script src="/js/jquery.min.js"></script>',
          '<script src="/js/styleguide.js"></script>',
        ],
        disableEncapsulation: true,
        title: 'Joinup styleguide',
        server: true,
        sideNav: true,
        rootPath: paths.styleguide,
        overviewPath: 'README.md',
        commonClass: 'body'
      }))
    .pipe(gulp.dest(paths.styleguide));
});
gulp.task('styleguide:applystyles', function() {
  return gulp.src(paths.sassStyleguide)
    .pipe(sass({
      errLogToConsole: true
    }))
    .pipe(styleguide.applyStyles())
    .pipe(gulp.dest(paths.styleguide));
});
gulp.task('styleguide', ['styleguide:generate', 'styleguide:applystyles']);
// Define copying images for styleguide task
gulp.task('images', function() {
  gulp.src(['../images/**'])
    .pipe(gulp.dest(paths.styleguide + '/images'))
    .pipe(gulp.dest('images'));
});
// Define copying javascript for styleguide task
gulp.task('js', function() {
  gulp.src(['js/**', '../vendor/material-design-lite/material.min.js', '../../../../web/core/assets/vendor/jquery/jquery.min.js'])
    .pipe(gulp.dest(paths.styleguide + '/js'));
});

// Listen folders for changes and apply defined tasks
gulp.task('default', ['styleguide', 'sass', 'images', 'js', 'mustache'], function() {
  livereload.listen(45729);
  gulp.watch([paths.sass, paths.html, paths.mustache], ['styleguide', 'sass', 'images', 'js', 'mustache']);
});
