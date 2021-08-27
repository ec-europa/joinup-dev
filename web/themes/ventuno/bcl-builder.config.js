const path = require("path");
const replace = require("@rollup/plugin-replace");

const outputFolder = path.resolve(__dirname);
const nodeModules = "./node_modules";

// SCSS includePaths
const includePaths = [nodeModules];

module.exports = {
  scripts: [
    {
      entry: path.resolve(outputFolder, "src/js/splide-config.js"),
      dest: path.resolve(outputFolder, "assets/js/splide-config.js"),
      options: {
        name: "splide",
        minify: false,
        sourceMap: true,
      },
    },
  ],
  styles: [
    {
      entry: path.resolve(outputFolder, "src/scss/styles.scss"),
      dest: path.resolve(outputFolder, "css/styles.css"),
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
          "@openeuropa/bcl-theme-joinup/icons/bootstrap-icons.svg"
        ),
      ],
      to: path.resolve(outputFolder, "assets/icons"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          nodeModules,
          "@splidejs/splide/dist/css/splide.min.css"
        ),
      ],
      to: path.resolve(outputFolder, "css"),
      options: { up: true },
    },
    {
      from: [
        path.resolve(
          nodeModules,
          "@splidejs/splide/dist/js/splide.min.js"
        ),
      ],
      to: path.resolve(outputFolder, "assets/js"),
      options: { up: true },
    },
  ],
};
