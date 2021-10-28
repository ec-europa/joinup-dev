const path = require("path");
const replace = require("@rollup/plugin-replace");

const outputFolder = path.resolve(__dirname);
const nodeModules = "./node_modules";

// SCSS includePaths
const includePaths = [nodeModules];

module.exports = {
  scripts: [
    {
      entry: path.resolve(outputFolder, "src/js/popovers.js"),
      dest: path.resolve(outputFolder, "assets/js/popovers.js"),
      options: {
        minify: false,
        sourceMap: true,
      },
    },
    {
      entry: path.resolve(outputFolder, "src/js/slick-config.js"),
      dest: path.resolve(outputFolder, "assets/js/slick-config.js"),
      options: {
        name: "slick",
        minify: false,
        sourceMap: true,
      },
    },
  ],
  styles: [
    {
      entry: path.resolve(outputFolder, "src/scss/main.scss"),
      dest: path.resolve(outputFolder, "assets/css/main.css"),
      options: {
        includePaths,
        sourceMap: "file",
      },
    },
    {
      entry: path.resolve(outputFolder, "src/scss/homepage.scss"),
      dest: path.resolve(outputFolder, "assets/css/homepage.css"),
      options: {
        includePaths,
        sourceMap: "file",
      },
    },
    {
      entry: path.resolve(outputFolder, "src/scss/overrides/tour.scss"),
      dest: path.resolve(outputFolder, "assets/css/tour.css"),
      options: {
        includePaths,
        sourceMap: "file",
      },
    },
  ],
  copy: [
    {
      from: [
        path.resolve(
          nodeModules,
          "@openeuropa/bcl-theme-joinup/js/oe-bcl-joinup.bundle.min.js"
        ),
      ],
      to: path.resolve(outputFolder, "assets/js"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          nodeModules,
          "@openeuropa/bcl-theme-joinup/icons/bootstrap-icons.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/icons"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/fonts/joinup-icons/*.woff*"
        ),
      ],
      to: path.resolve(outputFolder, "assets/fonts/joinup-icons"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/images/logo.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/images"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/images/highlighted-event.jpg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/images"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/images/iop-eu-logo.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/images"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/images/homepage-banner.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/images"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          __dirname,
          "src/images/homepage-banner-md.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/images"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          nodeModules,
          "slick-carousel/slick/slick.css"
        ),
      ],
      to: path.resolve(outputFolder, "assets/css"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          nodeModules,
          "slick-carousel/slick/slick.js"
        ),
      ],
      to: path.resolve(outputFolder, "assets/js"),
      options: { up: true },
    },
  ],
};
