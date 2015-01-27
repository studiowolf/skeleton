$ = require('jquery');
var modules = require('./modules');

module.exports = {

    // Init the app
    blastoff: function () {
        var self = window.app = this;

        // Wait for document ready to start the Javascript
        // this ensures the document has a body, etc.
        $(function() {


            self.loadModule({
                'module': 'navigation',
                'conditionalId': 'js-navigation'
            });


        });

        // Wait for all recources to be loaded before executing this scripts
        $(window).load(function() {



        });
    },

    // Load a specific module and give the possibility to use an
    // optional element ID
    loadModule: function(options) {

        if(typeof options.conditionalId === 'undefined' ||
            document.getElementById(options.conditionalId)) {
                modules[options.module]();
            }
        }
};

module.exports.blastoff();
