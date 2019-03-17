'use strict';

const gulp         = require('gulp');
const del          = require('del');
const rename       = require('gulp-rename');
const postcss      = require('gulp-postcss');
const sourcemaps   = require('gulp-sourcemaps');
const uglify       = require('gulp-uglify');
const newer        = require('gulp-newer');

gulp.task('clean:css', function() {
	return del(['assets/*.min.css', 'assets/*.css.map']);
})

gulp.task('clean:js', function() {
	return del(['assets/*.min.js', 'assets/*.js.map']);
});

gulp.task('clean', gulp.series(['clean:js', 'clean:css']));

gulp.task('css', function() {
	const src  = ['assets/*.css', '!assets/*.min.css'];
	const dest = 'assets/';
	return gulp.src(src)
		.pipe(newer({
			dest: dest,
			ext: '.min.css'
		}))
		.pipe(sourcemaps.init())
		.pipe(postcss([
			require('autoprefixer')({browsers: '> 5%'})
		]))
		.pipe(postcss([
			require('cssnano')()
		]))
		.pipe(rename({suffix: '.min'}))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(dest))
	;
});

gulp.task('js', function() {
	var src  = ['assets/*.js', '!assets/*.min.js'];
	var dest = 'assets/';
	return gulp.src(src)
		.pipe(newer({
			dest: dest,
			ext: '.min.js'
		}))
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(rename({suffix: '.min'}))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(dest))
	;
});

gulp.task('default', gulp.parallel(['css', 'js']));
