var gulp = require('gulp');
// Requires the gulp-sass plugin.
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function(){
  return gulp.src('src/scss/**/*.scss')
  	.pipe(sourcemaps.init())
    .pipe(sass({includePaths: ['node_modules']})) // Converts Sass to CSS with gulp-sass.
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('css'))

});

// Gulp watch syntax.
gulp.task('watch', function(){
  gulp.watch('src/scss/**/*.scss', gulp.series('sass'));
});