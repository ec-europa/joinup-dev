# Frontend tools

## Requirements

* Node.js with its `npm` command

## System setup

Check if the `gulp` npm package is available globally:
```Shell
$ which gulp
```
If it is not, run:
```Shell
$ npm -g install gulp
```

## Local development setup

* open a shell and go to the prototype folder in the joinup theme:
```Shell
$ cd web/themes/joinup/prototype
```
* install local node dependencies:
```Shell
$ npm install
```

## Available tools

`gulp` command will be available to compile scripts/sass/styleguide/prototype files.
Just run `gulp` without any parameters to compile and start the "watch" mode to compile files on change.
A list of all the tasks is available with `gulp --tasks`.
* The styleguide is available on your localhost - http://localhost:3000/.
* The prototypes are available in the folder web/themes/joinup/prototype/html-prototype. Just open any html file.
The prototypes can be changed in the folder web/themes/joinup/prototype/html-prototype-sandbox.
It is also the place to create html prototypes using mustache templating language.
