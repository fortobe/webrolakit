'use strict';
const
    autoprefixer = require('gulp-autoprefixer'),
    browserify = require('browserify'),
    browserSync = require('browser-sync').create(),
    buffer = require('vinyl-buffer'),
    gulp = require('gulp'),
    // pug = require('pug'),
    minify = require('gulp-minifier'),
    notify = require('gulp-notify'),
    plumber = require('gulp-plumber'),
    fileinclude = require('gulp-file-include'),
    markdown = require('markdown'),
    rename = require('gulp-rename'),
    rigger = require('gulp-rigger'),
    sass = require('gulp-sass'),
    vinylSource = require('vinyl-source-stream'),
    config = {
        path: {
            main: {
                srcDir: 'frontend/main',
                build: "www/assets",
                dir: "www",
            },
            common : 'frontend/common',
        },
        server: {
            proxy: "webrolakit",
            port: 2323,
            tunnel: false,
            host: 'localhost',
            open: false,
            notify: true,
            ui: {
                port: 2325,
            },
        },
    },
    path = {
        src: {
            main: {
                css: config.path.main.srcDir + "/styles/**/*.css",
                data: config.path.main.srcDir + "/data/**/*",
                fonts: config.path.main.srcDir + "/fonts/**/*.*",
                html: config.path.main.srcDir + "/**/[^_]*.html",
                images: config.path.main.srcDir + "/img",
                img: config.path.main.srcDir + "/img/**/*.*",
                js: config.path.main.srcDir + "/scripts/**/*.js",
                scripts: config.path.main.srcDir + "/scripts",
                scss: config.path.main.srcDir + "/styles/**/*.scss",
                styles: config.path.main.srcDir + "/styles",
                typography: config.path.main.srcDir + "/fonts",
                views: config.path.main.srcDir + "/**/*.html",
            },
            common: {
                lib: config.path.common + "/libs/**/*.*",
                styles: config.path.common + "/styles/**/*.*",
                scripts: config.path.common + "/scripts/**/*.js",
            },
        },
        build: {
            main: {
                css: config.path.main.build + "/css/",
                data: config.path.main.build + "/data/",
                fonts: config.path.main.build + "/fonts/",
                html: config.path.main.dir + '/',
                img: config.path.main.build + "/img/",
                js: config.path.main.build + "/js/",
            },
        },
    };

gulp.task('watch', function () {

    gulp.watch([
        path.src.main.data,
    ], gulp.parallel('data'));

    gulp.watch([
        path.src.main.views,
    ], gulp.parallel('view:html'));

    gulp.watch([
        path.src.main.img,
    ], gulp.parallel('view:img'));

    gulp.watch([
        path.src.main.fonts,
    ], gulp.parallel('view:fonts'));

    gulp.watch([
        path.src.main.scss,
        path.src.common.styles,
    ], gulp.parallel('css:styles'));

    gulp.watch([
        path.src.main.css,
    ], gulp.parallel('css:plugins'));

    gulp.watch([
        path.src.main.scripts + "/script.js",
        path.src.main.scripts + "/functions.js",
        path.src.common.scripts,
    ], gulp.parallel('js:script'));

    gulp.watch([
        path.src.main.scripts + "/app.js",
        path.src.main.scripts + "/functions.js",
        path.src.main.scripts + "/components/**/*.js",
        path.src.common.scripts,
    ], gulp.parallel('js:app'));

    gulp.watch([
        path.src.main.scripts + "/plugins.js",
    ], gulp.parallel('js:plugins'));

    browserSync.init(config.server);
});

gulp.task('view:html', function () {
    return gulp.src(path.src.main.html)
        .pipe(plumber({
            errorHandler: notify.onError('Rigger error: <%= error.message %>'),
        }))
        .pipe(fileinclude({
            prefix: '@@',
            pasepath: '@file',
            filters: {
                markdown: markdown.parse,
            },
        }))
        .pipe(gulp.dest(path.build.main.html))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('view:img', function () {
    return gulp.src(path.src.main.img)
        .pipe(plumber({
            errorHandler: notify.onError('IMG error: <%= error.message %>'),
        }))
        .pipe(gulp.dest(path.build.main.img))
        .pipe(browserSync.reload({stream: true,}));

});

gulp.task('view:fonts', function () {
    return gulp.src(path.src.main.fonts)
        .pipe(gulp.dest(path.build.main.fonts))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('css:styles', function () {
    return gulp.src(path.src.main.styles + '/main.scss')
        .pipe(plumber({
            errorHandler: notify.onError('SASS error: <%= error.message %>'),
        }))
        .pipe(sass({
            errLogToConsole: true,
        }))
        .pipe(autoprefixer())
        .pipe(buffer())
        .pipe(minify({
            minify: true,
            collapseWhitespace: true,
            conservativeCollapse: true,
            minifyCSS: true,
            getKeptComment: function (content) {
                let m = content.match(/\/\*![\s\S]*?\*\//img);
                return m && m.join('\n') + '\n' || '';
            },
        }))
        .pipe(rename('main.css'))
        .pipe(gulp.dest(path.build.main.css))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('css:plugins', function () {
    return gulp.src(path.src.main.styles + "/plugins.css")
        .pipe(plumber({
            errorHandler: notify.onError('Rigger error: <%= error.message %>'),
        }))
        .pipe(rigger())
        .pipe(buffer())
        .pipe(minify({
            minify: true,
            collapseWhitespace: true,
            conservativeCollapse: true,
            minifyJS: true,
            minifyCSS: true,
            getKeptComment: function (content) {
                let m = content.match(/\/\*![\s\S]*?\*\//img);
                return m && m.join('\n') + '\n' || '';
            },
        }))
        .pipe(gulp.dest(path.build.main.css))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('js:script', function () {
    return browserify(path.src.main.scripts + '/script.js')
        .transform("babelify", {presets: ["@babel/preset-env", "@babel/preset-react",],})
        .bundle().on('error', function (error) {
            let args = Array.prototype.slice.call(arguments);
            notify.onError('JS error: <%= error %>').apply(this, args);
            this.emit('end');
        })
        .pipe(vinylSource('script.js'))
        .pipe(buffer())
        .pipe(minify({
            minify: true,
            collapseWhitespace: true,
            conservativeCollapse: true,
            minifyJS: {
                sourceMap: true,
            },
            minifyCSS: true,
            getKeptComment: function (content) {
                let m = content.match(/\/\*![\s\S]*?\*\//img);
                return m && m.join('\n') + '\n' || '';
            },
        }))
        .pipe(gulp.dest(path.build.main.js))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('js:app', function () {
    return browserify(path.src.main.scripts + '/app.js')
        .transform("babelify", {presets: ["@babel/preset-env", "@babel/preset-react",],})
        .bundle().on('error', function (error) {
            let args = Array.prototype.slice.call(arguments);
            notify.onError('JS error: <%= error %>').apply(this, args);
            this.emit('end');
        })
        .pipe(vinylSource('app.js'))
        .pipe(buffer())
        .pipe(minify({
            minify: true,
            collapseWhitespace: true,
            conservativeCollapse: true,
            minifyJS: {
                sourceMap: true,
            },
            minifyCSS: true,
            getKeptComment: function (content) {
                let m = content.match(/\/\*![\s\S]*?\*\//img);
                return m && m.join('\n') + '\n' || '';
            },
        }))
        .pipe(gulp.dest(path.build.main.js))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('js:plugins', function () {
    return gulp.src(path.src.main.scripts + "/plugins.js")
        .pipe(plumber({
            errorHandler: notify.onError('JS Plugins error: <%= error.message %>'),
        }))
        .pipe(rigger())
        .pipe(buffer())
        .pipe(minify({
            minify: true,
            collapseWhitespace: true,
            conservativeCollapse: true,
            minifyJS: {
                sourceMap: true,
            },
            minifyCSS: true,
            getKeptComment: function (content) {
                let m = content.match(/\/\*![\s\S]*?\*\//img);
                return m && m.join('\n') + '\n' || '';
            },
        }))
        .pipe(gulp.dest(path.build.main.js))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('data', function () {
    return gulp.src(path.src.main.data)
        .pipe(plumber({
            errorHandler: notify.onError('DATA error: <%= error.message %>'),
        }))
        .pipe(gulp.dest(path.build.main.data))
        .pipe(browserSync.reload({stream: true,}));
});

gulp.task('default', gulp.parallel('watch'));

gulp.task('build:view', gulp.parallel('view:html', 'view:img', 'view:fonts'));
gulp.task('build:css', gulp.parallel('css:styles', 'css:plugins'));
gulp.task('build:js', gulp.parallel('js:script', 'js:plugins'));
gulp.task('build:data', gulp.parallel('data'));
gulp.task('build', gulp.parallel('build:view', 'build:css', 'build:js', 'build:data'));
