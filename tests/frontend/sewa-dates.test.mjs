import fs from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const view = fs.readFileSync(new URL('../../resources/views/sewas/form.blade.php', import.meta.url), 'utf8');
const calculation = view.match(/function calcTanggalKeluar\(\) \{[\s\S]*?\n    \}/)[0];

for (const [start, months, expected] of [
    ['2026-01-31', 3, '2026-04-30'],
    ['2026-01-31', 1, '2026-02-28'],
    ['2028-01-31', 1, '2028-02-29'],
    ['2026-12-31', 3, '2027-03-31'],
]) {
    test(`${start} plus ${months} calendar months`, () => {
        const fields = {
            lama_sewa: { value: String(months) },
            tanggal_masuk: { value: start },
            tanggal_keluar: { value: '' },
        };
        const context = { document: { getElementById: id => fields[id] }, baseKeluar: null };
        vm.createContext(context);
        vm.runInContext(calculation, context);
        context.calcTanggalKeluar();
        assert.equal(fields.tanggal_keluar.value, expected);
    });
}
