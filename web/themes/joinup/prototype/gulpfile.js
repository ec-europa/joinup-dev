// Gather used gulp plugins
var gulp = require('gulp'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    watch = require('gulp-watch'),
    sass = require('gulp-sass'),
    // styleguide = require('sc5-styleguide'),
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
gulp.task('sass', function (done) {
  gulp.src('sass/app.sass')
    .pipe(sass(
      {outputStyle: 'compressed'}
    ).on('error', sass.logError))
    .pipe(rename('style.min.css'))
    .pipe(gulp.dest('../css'))
    .pipe(gulp.dest('css'))
    .pipe(livereload());
  done();
});

// Define Mustache compiling task
gulp.task('mustache', function(done) {
  return gulp.src("./html-prototype-sandbox/*.html")
    .pipe(mustache())
    .pipe(gulp.dest("./html-prototype"));
});

// Define copying images for styleguide task
gulp.task('images', function(done) {
  gulp.src(['../images/**'])
    .pipe(gulp.dest(paths.styleguide + '/images'))
    .pipe(gulp.dest('images'));
  done();
});

// Define copying fonts for styleguide task
gulp.task('fonts', function(done) {
  gulp.src(['../fonts/**'])
    .pipe(gulp.dest(paths.styleguide + '/fonts'))
    .pipe(gulp.dest('fonts'));
  done();
});

// Define copying javascript for styleguide task
gulp.task('js', function(done) {
  gulp.src(['js/**', '../vendor/material-design-lite/material.min.js', '../../../../web/core/assets/vendor/jquery/jquery.min.js'])
    .pipe(gulp.dest(paths.styleguide + '/js'));
  done();
});

// Define watch tasks
gulp.task('watch', function(done) {
  livereload.listen(45729);
  gulp.watch(paths.sass, gulp.series('sass'));
  gulp.watch(paths.html, gulp.series('images', 'fonts', 'js'));
  gulp.watch(paths.mustache, gulp.series('mustache'));
  done();
});

// Listen folders for changes and apply defined tasks
gulp.task('default', gulp.parallel('sass', 'images', 'fonts', 'js', 'mustache'), function(done) {
  done();
});
