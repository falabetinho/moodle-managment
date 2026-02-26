module.exports = function(grunt) {
    'use strict';

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Compress task for creating distributable ZIP
        compress: {
            main: {
                options: {
                    archive: 'dist/moodle-management-<%= pkg.version %>.zip',
                    mode: 'zip',
                    level: 9,
                    pretty: true
                },
                files: [
                    {
                        expand: true,
                        src: [
                            '**',
                            '!node_modules/**',
                            '!dist/**',
                            '!.git/**',
                            '!.gitignore',
                            '!package.json',
                            '!package-lock.json',
                            '!Gruntfile.js',
                            '!.vscode/**',
                            '!.idea/**',
                            '!*.log',
                            '!.DS_Store',
                            '!Thumbs.db'
                        ],
                        dest: 'moodle-management/'
                    }
                ]
            }
        }
    });

    // Load tasks
    grunt.loadNpmTasks('grunt-contrib-compress');

    // Register tasks
    grunt.registerTask('default', ['compress']);
    grunt.registerTask('build', ['compress']);
    grunt.registerTask('zip', ['compress']);
};
