'use strict';

import gulp from 'gulp';
import archiver from 'gulp-archiver';
import clean from 'gulp-clean';
import composer from 'gulp-composer';
import copy from 'gulp-copy';
import fs from 'fs';
import phpcs from 'gulp-phpcs';
import makepot from 'gulp-wp-pot'

const pkg = require('./package.json');

const php_src = ['*.php', 'admin/**/*.php', 'includes/**/*.php', 'public/**/*.php', 'eb-mods/*.php'];

gulp.task('phpcs', () => {
	return gulp.src(php_src)
		.pipe(phpcs({
			bin: 'vendor/bin/phpcs',
			standard: 'codesniffer.ruleset.xml',
			showSniffCode: true
		}))
		.pipe(phpcs.reporter('log'));
});

gulp.task('test', gulp.series('phpcs'));

gulp.task('makepot', () => {
	return gulp.src(php_src)
		.pipe(makepot({
			domain: pkg.name,
			package: pkg.name,
			metadataFile: 'calendar-plus.php',
		}))
		.pipe(gulp.dest(`languages/${pkg.name}.pot`));
});

gulp.task('i18n', gulp.parallel('makepot'));

gulp.task('default', gulp.parallel('i18n'));

gulp.task('package', gulp.series(
	'default',
	// remove files from last run
	() => gulp.src(['dist', pkg.name, `${pkg.name}.*.zip`], {read: false, allowEmpty: true})
		.pipe(clean()),

	// remove composer dev dependencies
	() => composer({'no-dev': true}),

	// copy files into a new directory
	() => gulp.src(['**/*', '!node_modules/**/*'])
		.pipe(copy(pkg.name)),

	// create the archive file
	() => gulp.src(pkg.name + '/**/*', {base: '.'})
		.pipe(archiver(`${pkg.name}.${pkg.version}.zip`))
		.pipe(gulp.dest('.')),

	(done) => {
		// reinstall dev dependencies
		composer();

		// rename the distribution directory to its proper name
		fs.rename(pkg.name, 'dist', err => {
			if (err) throw err;
			done();
		});
	}
));
