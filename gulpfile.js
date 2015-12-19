/*!
 * gulp
 * $ npm install gulp-sourcemaps gulp-load-plugins gulp-ruby-sass gulp-autoprefixer gulp-minify-css gulp-jshint gulp-concat gulp-uglify gulp-imagemin gulp-notify gulp-rename gulp-livereload gulp-cache del --save-dev
 */

// Load plugins
var gulp = require('gulp'),
	del = require('del'),
	$ = require('gulp-load-plugins')();

var styles_path = [
	'public/assets/source/css/reset.css',
	'public/assets/source/css/fonts.css',
	'public/assets/source/css/default.css',
	'public/assets/source/css/check.css',
	'public/assets/source/css/fancy.css',
	'public/assets/source/css/icons.css'
];
var scripts_path = [
	'public/assets/source/js/j.value.js',
	'public/assets/source/js/j.periodic.js',
	'public/assets/source/js/j.url.js',
	'public/assets/source/js/j.textarea.js',
	'public/assets/source/js/j.area.js',
	'public/assets/source/js/j.input-complete.js',
	'public/assets/source/js/g.js'
];
var images_path = 'public/assets/source/images/**/*';

var styles_dest = 'public/assets/';
var scripts_dest = 'public/assets/';
var images_dest = 'public/assets/images/';

gulp.task('browser-sync', function() {
    $.browserSync.create().init({
        proxy: "dev.republicarock.com"
    });
});

gulp.task('styles', function() {
	return gulp.src(styles_path)
		.pipe($.sourcemaps.init())
		.pipe($.autoprefixer('last 2 version'))
		.pipe($.concat('g.css'))
		.pipe(gulp.dest(styles_dest))
		.pipe($.rename({ suffix: '.min' }))
		.pipe($.minifyCss())
		.pipe($.sourcemaps.write('.', {
			sourceMappingURL: function(file) {
				return file.relative + '.map';
			}
		}))
		.pipe(gulp.dest(styles_dest))
		.pipe($.notify({ message: 'Styles task complete' }));
});

gulp.task('scripts', function() {
  return gulp.src(scripts_path)
	// .pipe(jshint('.jshintrc'))
	// .pipe(jshint.reporter('default'))
	
	.pipe($.concat('g.js'))
	.pipe(gulp.dest(scripts_dest))
	.pipe($.rename({ suffix: '.min' }))
	.pipe($.uglify())
	.pipe(gulp.dest(scripts_dest))
	.pipe($.notify({ message: 'Scripts task complete' }));
});

gulp.task('images', function() {
  return gulp.src(images_path)
	.pipe($.cache($.imagemin({
		optimizationLevel: 3,
		progressive: true,
		interlaced: true
	})))
	.pipe(gulp.dest(images_dest));
	// .pipe(notify({ message: 'Images task complete' }));
});

gulp.task('clean', function(cb) {
	del([styles_dest + '*.css', scripts_dest + '*.js', images_dest], cb)
});

gulp.task('default', ['clean', 'styles', 'scripts', 'images', 'watch']);

gulp.task('watch', function() {
	gulp.watch(styles_path, ['styles']);
	gulp.watch(scripts_path, ['scripts']);
	gulp.watch(images_path, ['images']);

	// livereload.listen();
	// gulp.watch(['public/**']).on('change', livereload.changed);
});