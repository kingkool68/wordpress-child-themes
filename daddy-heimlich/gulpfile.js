/*jslint node: true */

/**
 * Dependencies
 */

// Gulp Dependencies
var gulp = require('gulp');
var notify = require("gulp-notify");
var sourcemaps = require('gulp-sourcemaps');

// Other Dependencies
var colors = require('colors');
var sequence = require('run-sequence');
var del = require('del');

/**
 * Config
 */

// Check for --production flag
var argv = require('yargs').argv;
var isProduction = !!(argv.production);

// Browsers to target when prefixing CSS.
var COMPATIBILITY = [
	'last 10 versions',
	'ie >= 6',
	'Android >= 2.3'
];

// File paths to various assets are defined here.
var wpIncludesDir = '../../../wp-includes/js';
var PATHS = {
	javascript: [
		wpIncludesDir + '/wp-embed.js',
		wpIncludesDir + '/mediaelement/mediaelement-and-player.js',
		wpIncludesDir + '/mediaelement/mediaelement-migrate.js',
		wpIncludesDir + '/mediaelement/wp-mediaelement.js',
		'js/instantpage.js',
		'js/menu.js',
		'js/analytics.js',
		'js/infinite-scroll.js'
	],
	scss: [
		'../vera-heimlich/scss/*.scss',
		'../zadie-heimlich/scss/*.scss'
	],
	phpcs: [
		'**/*.php',
		'!wpcs',
		'!wpcs/**',
	]
};

/**
 * Compile Sass into CSS
 */
var autoprefixer = require('gulp-autoprefixer');
var sass = require('gulp-sass');
var rename = require('gulp-rename');
var cssnano = require('gulp-cssnano');
var clone = require('gulp-clone');
var merge = require('merge-stream');
gulp.task('sass', function () {

	var fixedDestination = function (file) {
		var newPath = file.path.split('/scss/')[0];
		newPath += '/css';
		return newPath;
	};

	var source = gulp.src(PATHS.scss)
		// Start process for sourcemaps
		.pipe(sourcemaps.init())

		// Process sass files
		.pipe(sass())

		// Listen for errors
		.on('error', notify.onError({
			message: "<%= error.message %>",
			title: "Sass Error"
		}))

		// Autoprefix
		.pipe(autoprefixer({
			browsers: COMPATIBILITY
		}));

	// Split the pipes to prevent a weird error with cssnano
	// See https://github.com/ben-eb/gulp-cssnano/issues/38#issuecomment-186593234
	var normalPipe = source.pipe(clone())
		// Write the sourcemaps
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(fixedDestination));

	var minifiedPipe = source.pipe(clone())
		// Create a new file for the minified verison
		.pipe(rename({
			suffix: '.min'
		}))

		// Optimize minified version of CSS
		.pipe(cssnano())

		// Write out sourcemaps for the minified version
		.pipe(sourcemaps.write('.'))

		// Save the output to disk
		.pipe(gulp.dest(fixedDestination));

	// Merge the pipes together
	return merge(normalPipe, minifiedPipe);
});

/**
 * Linting & Code Sniffing
 */

// JavaScript linting
var jshint = require('gulp-jshint');
var stylish = require('jshint-stylish');
gulp.task('lint', function () {
	return gulp.src([
		'js/*.js',
		'!js/*.min.js'
	])
		.pipe(jshint())
		.pipe(jshint.reporter(stylish));
});

// PHP Code Sniffer task
var phpcs = require('gulp-phpcs');
gulp.task('phpcs', function () {
	return gulp.src(PATHS.phpcs)
		.pipe(phpcs({
			bin: 'wpcs/vendor/bin/phpcs',
			standard: './phpcs.ruleset.xml',
			showSniffCode: true,
		}))
		.pipe(phpcs.reporter('log'));
});

// PHP Code Beautifier task
var phpcbf = require('gulp-phpcbf');
var gutil = require('gulp-util');

gulp.task('phpcbf', function () {
	return gulp.src(PATHS.phpcs)
		.pipe(phpcbf({
			bin: 'wpcs/vendor/bin/phpcbf',
			standard: './phpcs.ruleset.xml',
			warningSeverity: 0
		}))
		.on('error', gutil.log)
		.pipe(gulp.dest('.'));
});

/**
 * JavaScript Concatenation
 */
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
gulp.task('javascript:global', function () {

	return gulp.src(PATHS.javascript)
		.pipe(concat('global.min.js'))
		// .pipe(sourcemaps.init())
		.pipe(uglify())
		// .pipe(sourcemaps.write())
		.pipe(gulp.dest('js'));
});

gulp.task('javascript:min', function () {

	return gulp.src([
		'js/*.js',
		'!js/*.min.js'
	])
		// Create a new file for the minified verison
		.pipe(rename({
			suffix: '.min'
		}))
		// Uglify
		.pipe(uglify())
		// Save
		.pipe(gulp.dest('js'));
});

gulp.task('javascript', ['javascript:global', 'javascript:min']);

/**
 * Build Task
 */
// Build task
// Runs sass & javascript in parallel
gulp.task('build', ['clean'], function (done) {
	sequence(['sass', 'javascript', 'lint'], done);
});


/**
 * Cleaning taks (deleting old version)
 */
gulp.task('clean', function (done) {
	sequence(['clean:javascript', 'clean:css'], done);
});

gulp.task('clean:javascript', function () {
	return del([
		'js/*.min.js',
		'!js/jquery-3.min.js'
	]);
});

gulp.task('clean:css', function () {
	return del([
		'../vera-heimlich/css/*',
		'../zadie-heimlich/css/*'
	], { force: true });
});

/**
 * Default Task
 */
// Run build task and watch for file changes
gulp.task('default', ['build'], function () {
	// Log file changes to console
	function logFileChange(event) {
		var fileName = require('path').relative(__dirname, event.path);
		console.log('[' + 'WATCH'.green + '] ' + fileName.magenta + ' was ' + event.type + ', running tasks...');
	}

	// Sass Watch
	gulp.watch(['../**/scss/**/*.scss'], ['clean:css', 'sass'])
		.on('change', function (event) {
			logFileChange(event);
		});

	// JS Watch
	gulp.watch(['js/*.js'], ['clean:javascript', 'javascript', 'lint'])
		.on('change', function (event) {
			logFileChange(event);
		});
});
