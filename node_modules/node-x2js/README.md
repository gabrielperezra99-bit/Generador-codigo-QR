# node-x2js

![Node](https://img.shields.io/node/v/node-x2js.svg?style=flat-square)
[![NPM](https://img.shields.io/npm/v/node-x2js.svg?style=flat-square)](https://www.npmjs.com/package/node-x2js)
[![Travis](https://img.shields.io/travis/liuy97/node-x2js/master.svg?style=flat-square)](https://travis-ci.org/liuy97/node-x2js)
[![David](https://img.shields.io/david/liuy97/node-x2js.svg?style=flat-square)](https://david-dm.org/liuy97/node-x2js)
[![Coverage Status](https://img.shields.io/coveralls/liuy97/node-x2js.svg?style=flat-square)](https://coveralls.io/github/liuy97/node-x2js)

> x2js - XML to JSON and vice versa for JavaScript

### Usage

```js
import X2JS from 'node-x2js';

```

### Installation

Install via [yarn](https://github.com/yarnpkg/yarn)

	yarn add node-x2js (--dev)

or npm

	npm install node-x2js (--save-dev)


### configuration

You can pass in extra options as a configuration object (➕ required, ➖ optional, ✏️ default).

```js
import X2JS from 'node-x2js';

```

➖ **property** ( type ) ` ✏️ default `
<br/> 📝 description
<br/> ❗️ warning
<br/> ℹ️ info
<br/> 💡 example

### methods

#### #name

```js
nodeX2js

```

### Examples

See [`example`](example/script.js) folder or the [runkit](https://runkit.com/liuy97/node-x2js) example.

### Builds

If you don't use a package manager, you can [access `node-x2js` via unpkg (CDN)](https://unpkg.com/node-x2js/), download the source, or point your package manager to the url.

`node-x2js` is compiled as a collection of [CommonJS](http://webpack.github.io/docs/commonjs.html) modules & [ES2015 modules](http://www.2ality.com/2014/0
  -9/es6-modules-final.html) for bundlers that support the `jsnext:main` or `module` field in package.json (Rollup, Webpack 2)

The `node-x2js` package includes precompiled production and development [UMD](https://github.com/umdjs/umd) builds in the [`dist` folder](https://unpkg.com/node-x2js/dist/). They can be used directly without a bundler and are thus compatible with many popular JavaScript module loaders and environments. You can drop a UMD build as a [`<script>` tag](https://unpkg.com/node-x2js) on your page. The UMD builds make `node-x2js` available as a `window.nodeX2js` global variable.

### License

The code is available under the [Apache-2.0](LICENSE) license.

### Contributing

We are open to contributions, see [CONTRIBUTING.md](CONTRIBUTING.md) for more info.

### Misc

This module was created using [generator-module-boilerplate](https://github.com/duivvv/generator-module-boilerplate).
