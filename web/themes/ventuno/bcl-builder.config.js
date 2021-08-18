const path = require("path");
const replace = require("@rollup/plugin-replace");

const outputFolder = path.resolve(__dirname);
const nodeModules = "./node_modules";

// SCSS includePaths
const includePaths = [nodeModules];

module.exports = {
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
  ],
};
