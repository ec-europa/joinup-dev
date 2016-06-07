var gulp = require('gulp'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    watch = require('gulp-watch'),
    sass = require('gulp-sass'),
    styleguide = require('sc5-styleguide'),
    livereload = require('gulp-livereload');

var paths = {
  sass: ['sass/**/*.+(scss|sass)'],
  sassStyleguide: ['sass/**/*.+(scss|sass)', '!sass/_mixins.+(scss|sass)'],
  html: ['sass/**/*.html'],
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

gulp.task('images', function() {
  gulp.src(['images/**'])
    .pipe(gulp.dest(paths.styleguide + '/images'));
});

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

gulp.task('styleguide:generate', function() {
  return gulp.src(paths.sassStyleguide)
    .pipe(styleguide.generate({
        title: 'Joinup',
        server: true,
        rootPath: paths.styleguide,
        overviewPath: 'README.md',
        commonClass: 'body'
      }))
    .pipe(gulp.dest(paths.styleguide));
});

gulp.task('styleguide:applystyles', function() {
  return gulp.src('sass/app.sass')
    .pipe(sass({
      errLogToConsole: true
    }))
    .pipe(styleguide.applyStyles())
    .pipe(gulp.dest(paths.styleguide));
});

gulp.task('styleguide', ['styleguide:generate', 'styleguide:applystyles']);

gulp.task('default', ['styleguide', 'sass', 'images'], function() {
  livereload.listen();
  gulp.watch([paths.sass, paths.html], ['styleguide', 'sass', 'images']);
});
