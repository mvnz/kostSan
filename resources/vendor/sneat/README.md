# Sneat source provenance

Unmodified source from ThemeSelection's MIT-licensed Sneat Bootstrap HTML Admin Template Free 3.0.0:

- Repository: https://github.com/themeselection/sneat-bootstrap-html-admin-template-free
- Pinned revision: `de094c2bf89e602054b7c14f46c9a3a5b9f6cac9`
- `helpers.js` and `menu.js`: upstream `js/`
- `config.js` and `main.js`: upstream `assets/js/`
- `LICENSE`: upstream MIT notice retained here and copied to the built distribution.

These source modules are compiled as production classic IIFEs by `vite.ui.config.js`. Do not replace them with upstream development bundles in `assets/vendor/js`: those use webpack eval and fail under this application's CSP. npm supplies locked jQuery, Bootstrap/Popper, Perfect Scrollbar and DataTables versions; do not vendor their compiled development bundles.

When updating, review the upstream revision/license/API, update the matching pinned Sneat CSS revision in Blade, build without eval and run the full browser/regression suite with CSP enabled. Existing inline Blade scripts require synchronous globals, so changing these script tags to deferred modules needs a separate migration.
