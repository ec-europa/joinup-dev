#!/bin/sh
pwd
printenv

./vendor/bin/run dev:check-deprecated-code-contrib
