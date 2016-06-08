/*!
 * gulp
 * $ npm install gulp-sourcemaps gulp-load-plugins gulp-ruby-sass gulp-autoprefixer gulp-minify-css gulp-jshint gulp-concat gulp-uglify gulp-imagemin gulp-notify gulp-rename gulp-livereload gulp-cache del --save-dev
 */

// 
// Load plugins
// 
var gulp = require('gulp'),
	nib  = require('nib'),
	$    = require('gulp-load-plugins')();

var resources = 'resources/';
var destination = 'public/assets/';

var styles_path = [
	resources + 'css/reset.css',
	resources + 'css/fonts.css',
	resources + 'css/default.css',
	resources + 'css/check.css',
	resources + 'css/fancy.css',
	resources + 'css/icons.css'
];
var scripts_path = [
	resources + 'js/j.value.js',
	resources + 'js/j.periodic.js',
	resources + 'js/j.url.js',
	resources + 'js/j.textarea.js',
	resources + 'js/j.area.js',
	resources + 'js/j.input-complete.js',
	resources + 'js/g.js'
];
var images_path = resources + 'images/**/*';

var destination_images = destination + 'images/';

// 
// Define tasks
// 
gulp.task('css', function() {
	return gulp.src(styles_path)
		.pipe($.sourcemaps.init())
		.pipe($.stylus({ use: nib() }))
		.pipe($.concatCss('g.css'))
		.pipe($.autoprefixer('last 2 version'))
		.pipe(gulp.dest(destination))
		.pipe($.rename({ suffix: '.min' }))
		.pipe($.cssnano())
		.pipe($.sourcemaps.write('.', {
			sourceMappingURL: function(file) {
				return file.relative + '.map';
			}
		}))
		.pipe(gulp.dest(destination));
});

gulp.task('js', function() {
	return gulp.src(scripts_path)
		.pipe($.concat('g.js'))
		.pipe(gulp.dest(destination))
		.pipe($.rename({ suffix: '.min' }))
		.pipe($.uglify())
		.pipe(gulp.dest(destination));
});

gulp.task('images', function() {
	return gulp.src(images_path)
		.pipe($.cache($.imagemin({
		optimizationLevel: 3,
		progressive: true,
		interlaced: true
	})))
	.pipe(gulp.dest(destination_images));
});

gulp.task('clean', function(cb) {
	gulp.src([destination + '*.css', destination + '*.js', destination_images + '**/*'], {read: false})
	.pipe($.rimraf());
});

gulp.task('default', ['clean', 'css', 'js', 'images'/*, 'watch'*/]);

gulp.task('watch', function() {
	gulp.watch(styles_path, ['css']);
	gulp.watch(scripts_path, ['js']);
	gulp.watch(images_path, ['images']);
});

gulp.task('bs', ['default'], function() {
	$.browserSync.create().init({
		startPath: "/",
        open: "local", // external | ui
        online: true,
        logLevel: "info",
        port: 3000,
        proxy: "dev.republicarock.com",
        files: [],
        directory: true,
        ui: {
        	port: 9080,
        	weinre: {
        		port: 9090
        	}
        },
        serveStatic: []
	});

	gulp.watch([resources + '**/*.styl', resources + '**/*.css'], ['css']).on('change', $.browserSync.reload);
    gulp.watch([resources + '**/*.js', resources + '**/*.coffee'], ['js']).on('change', $.browserSync.reload);
    gulp.watch('**/*.php').on('change', $.browserSync.reload);
});
