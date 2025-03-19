'use strict';

import gulp from 'gulp';
import clean from 'gulp-clean';
import fs from 'fs';
import phpcs from 'gulp-phpcs';
import makepot from 'gulp-wp-pot';
import zip from 'gulp-zip';

const pkg = require('./package.json');

const phpFiles = ['*.php', 'admin/**/*.php', 'includes/**/*.php', 'public/**/*.php', 'eb-mods/*.php'];
const excludeFiles = ['node_modules/**/*', 'dist/**/*', 'gulpfile.babel.js', 'vendor/dealerdirect/**/*', 'vendor/squizlabs/**/*', 'vendor/wp-coding-standards/**/*', 'vendor/bin/**/*', 'vendor/phpunit/**/*'];

/**
 * Run PHP CodeSniffer
 */
gulp.task('phpcs', () => {
    console.log('🔹 Running PHP CodeSniffer...');
    return gulp.src(phpFiles)
        .pipe(phpcs({
            bin: 'vendor/bin/phpcs',
            standard: 'codesniffer.ruleset.xml',
            showSniffCode: true
        }))
        .pipe(phpcs.reporter('log'));
});

/**
 * Generate translation files
 */
gulp.task('makepot', () => {
    console.log('🔹 Generating translation files...');
    return gulp.src(phpFiles)
        .pipe(makepot({
            domain: pkg.name,
            package: pkg.name,
            metadataFile: 'calendar-plus.php',
        }))
        .pipe(gulp.dest(`languages/${pkg.name}.pot`));
});

/**
 * Default task - Ensures translations are generated before anything else
 */
gulp.task('default', gulp.series('makepot'));

/**
 * Clean previous build
 */
gulp.task('clean', () => {
    console.log('🔹 Cleaning previous build...');
    return gulp.src(['dist', `${pkg.name}.zip`], { read: false, allowEmpty: true })
        .pipe(clean());
});

/**
 * Copy required files for packaging
 */
gulp.task('copy', () => {
    console.log('🔹 Copying project files...');
    return gulp.src(['**/*', ...excludeFiles.map(path => `!${path}`)], { dot: true, nodir: true })
        .pipe(gulp.dest('dist/' + pkg.name));
});

/**
 * Create ZIP package
 */
gulp.task('zip', () => {
    console.log('🔹 Creating ZIP archive...');
    return gulp.src(`dist/${pkg.name}/**/*`, { base: 'dist' })
        .pipe(zip(`${pkg.name}.${pkg.version}.zip`))
        .pipe(gulp.dest('.'))
        .on('end', () => console.log('✅ ZIP created successfully!'));
});

/**
 * Main package task
 */
gulp.task('package', gulp.series(
    'clean',
    'copy',
    'zip'
));
