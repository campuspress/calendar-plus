'use strict';

import gulp from 'gulp';
import clean from 'gulp-clean';
import fs from 'fs';
import phpcs from 'gulp-phpcs';
import makepot from 'gulp-wp-pot';
require('@ilabdev/copy')(gulp);

const pkg = require("./package.json");

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

gulp.task('clean', 	() => {
	return gulp.src(['dist', pkg.name], {read: false, allowEmpty: true})
				.pipe(clean());
 });

gulp.task('package', gulp.series(
	'default',
	// remove files from last run
	"clean",
	// copy files into a new directory
	"copy",

	(done) => {
		// rename the distribution directory to its proper name
		fs.rename(pkg.name, 'dist', err => {
			if (err) throw err;
			done();
		});
	}
));
