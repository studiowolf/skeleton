var gulp = require('gulp'),
    sass = require('gulp-sass'),
    shell = require('gulp-shell'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    filter = require('gulp-filter'),
    imagemin = require('gulp-imagemin'),
    browserify = require('browserify'),
    rimraf = require('gulp-rimraf'),
    cache = require('gulp-cache'),
    gutil = require('gulp-util'),
    notify = require('gulp-notify'),
    streamify = require('gulp-streamify'),
    gulpif = require('gulp-if'),
    source = require('vinyl-source-stream'),
    browserSync = require('browser-sync');

var production = false;

// Default theme
var theme = 'v1';

if(gutil.env.theme) {
  theme = gutil.env.theme;
}

gulp.task('styles', function () {
  gulp.src('./content/themes/' + theme + '/assets/stylesheets/styles.scss')
    //.pipe(sass({sourceComments: 'map', sourceMap: 'sass'}))
    .pipe(sass())
    .on('error', handleErrors)
    .pipe(autoprefixer({
      browsers: ['last 2 versions'],
      cascade: false
    }))
    // IE7/8 CSS support is disbabled on default!
    .pipe(gulpif(production, minifycss({'noAdvanced': true})))
    .pipe(gulp.dest('content/themes/' + theme + '/compiled-assets/stylesheets'))
    .pipe(filter('**/*.css')) // Filtering stream to only css files
    .pipe(browserSync.reload({stream: true}));
});

// @TODO: zorgen dat deze niet crasht bij een JS foutje
gulp.task('scripts', function() {
  return browserify('./content/themes/' + theme + '/assets/scripts/index.js')
    .bundle({
      insertGlobals : true,
      debug: !production
    })
    .on('error', handleErrors)
    .pipe(source('bundle.js'))
    .pipe(gulpif(production, streamify(uglify())))
    .pipe(gulp.dest('content/themes/' + theme + '/compiled-assets/scripts'))
    .pipe(browserSync.reload({stream:true, once: true}));
});


gulp.task('images', function() {
  if(production) {
    return gulp.src('./content/themes/' + theme + '/assets/images/**/*')
      //.pipe(cache(imagemin({ optimizationLevel: 5, progressive: true, interlaced: true })))
      .pipe(gulp.dest('content/themes/' + theme + '/compiled-assets/images'));
  } else {
    return gulp.src('./content/themes/' + theme + '/assets/images/**/*')
      .pipe(gulp.dest('content/themes/' + theme + '/compiled-assets/images'));
  }
});


gulp.task('fonts', function() {
  return gulp.src('./content/themes/' + theme + '/assets/fonts/**/*')
    .pipe(gulp.dest('content/themes/' + theme + '/compiled-assets/fonts'));
});


// Start the php server
gulp.task('server', shell.task([
  'ulimit -S -n 2048',
  'php -S ' + '127.0.0.1:8000 router.php'
], {quiet: true}))
//this.emit('end');

// Start browser sync
gulp.task('browser-sync', function() {
    browserSync.init({
        proxy: '127.0.0.1:8000',
        xip: true,
        notify: false
    });
});


// Reload all browsers
gulp.task('bs-reload', function () {
    browserSync.reload();
});


gulp.task('clean', function() {
  //gulp.src('./content/themes/' + theme + '/compiled-assets/**/*', { read: false }).pipe(rimraf());
  return gulp.src('./content/themes/' + theme + '/compiled-assets', {read:false})
    .pipe(rimraf());
});


gulp.task('default', ['clean'], function() {
    production = false;
    gulp.start('watch');
});


gulp.task('build', ['clean'], function() {
  production = true;
  gulp.start('images', 'fonts', 'scripts', 'styles');
});

// @TODO: zorgen dat ook nieuwe files worden gewatcht
// Watch task while developing
gulp.task('watch', ['images', 'fonts', 'scripts', 'styles'], function() {

  // Execute the tasks first
  gulp.start('server', 'browser-sync');

  // Watch styles, scripts and images files
  gulp.watch(['./content/themes/' + theme + '/**/*.php', './raw/**/*.html'], ['bs-reload']);
  gulp.watch('./content/themes/' + theme + '/assets/scripts/**/*.js', ['scripts']);
  gulp.watch('./content/themes/' + theme + '/assets/stylesheets/**/*.scss', ['styles']);
  gulp.watch('./content/themes/' + theme + '/assets/fonts/**/*', ['fonts']);
  gulp.watch('./content/themes/' + theme + '/assets/images/**/*', ['images']);

  // Enable the watch of the plugin directory
  // gulp.watch(['./content/plugins/**/*.php'], ['bs-reload']);

});


var handleErrors = function() {

      var args = Array.prototype.slice.call(arguments);

      // Send error to notification center with gulp-notify
      notify.onError({
        title: "Compile Error",
        message: "<%= error.message %>"
      }).apply(this, args);

      this.emit('end');
    }
