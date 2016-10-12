# Frontend tools

## Requirements

* Node.js with its `npm` command
* Ruby with its `gem` command

## System setup

Check if the `gulp` npm package is available globally:
```Shell
$ which gulp
```
If it is not, run:
```Shell
$ npm -g install gulp
```

Do the same for the `bundler` gem:
```Shell
$ which bundle # Note: without "r" for the executable
```
If it's not available, run the following commands:
```Shell
$ gem install bundler # For generic systems
$ rvm @global do gem install bundler # When using RVM (https://rvm.io/)
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
* install ruby dependencies:
```Shell
$ bundle install
```

## Available tools

`gulp` command will be available to compile scripts/sass/styleguide/prototype files.
Just run `gulp` without any parameters to compile and start the "watch" mode to compile files on change.
A list of all the tasks is available with `gulp --tasks`. The styleguide is available on your localhost - http://localhost:3000/. The prototypes are available in folder web/themes/joinup/prototype/html-prototype.
