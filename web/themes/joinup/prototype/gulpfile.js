// Gather used gulp plugins
var gulp = require('gulp'),
    rename = require('gulp-rename'),
    watch = require('gulp-watch'),
    sass = require('gulp-sass'),
    livereload = require('gulp-livereload');

// Set paths
var paths = {
  sass: ['scss/**/*.scss','scss/**/*.scss' ],
};

// Define SASS compiling task
gulp.task('sass', function (done) {
  gulp.src('./scss/**/*.scss')
    .pipe(sass(
      {outputStyle: 'compressed'}
    ).on('error', sass.logError))
    .pipe(rename('style.min.css'))
    .pipe(gulp.dest('../css')) 
    .pipe(gulp.dest('css'))
    .pipe(livereload());
  done();
});

// Define watch tasks
gulp.task('watch', function(done) {
  livereload.listen(45729);
  gulp.watch(paths.sass, gulp.series('sass'));
  done();
});

// Listen folders for changes and apply defined tasks
gulp.task('default', gulp.parallel('sass'), function(done) {
  done();
});
