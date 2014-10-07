$ = require('jquery');
modules = require('./modules');

module.exports = {
    // this is the the whole app initter
    blastoff: function () {
        var self = window.app = this;

        // wait for document ready to start the Javascript
        // this ensures the document has a body, etc.
        $(function() {

            // Load a module conditional by ID, ie:
            // self.loadModuleConditional('navigation', 'js-navigation-wrapper');

        });
    },

    // Check if an element with a certain ID exists
    // We use ID's here because its faster
    loadModuleConditional: function(module, id) {
        if(document.getElementById(id)) {
            modules[module]();
        }
    }
};

module.exports.blastoff();
