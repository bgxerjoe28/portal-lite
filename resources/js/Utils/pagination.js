/**
 * Helper dan Composable Standarisasi Pagination untuk Portal SMA
 * Digunakan untuk menyamakan konfigurasi DataTable PrimeVue di seluruh sistem.
 */
import { ref, computed } from 'vue';

// Standar pilihan opsi baris per halaman di seluruh portal
export const DEFAULT_PAGE_SIZE = 10;
export const DEFAULT_ROWS_PER_PAGE_OPTIONS = [10, 50, 100];
export const DEFAULT_PAGINATOR_TEMPLATE = 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport';
export const DEFAULT_REPORT_TEMPLATE = 'Menampilkan {first} s.d {last} dari {totalRecords} data';

/**
 * Menghasilkan konfigurasi props standar pagination untuk komponen PrimeVue DataTable.
 * 
 * @example
 * // Penggunaan di template Vue:
 * <DataTable :value="items" v-bind="getPaginationProps({ label: 'siswa' })">
 * // Atau via global helper:
 * <DataTable :value="items" v-bind="$pagination({ label: 'siswa' })">
 * 
 * @param {Object} [options={}] - Opsi kustomisasi
 * @param {number} [options.rows=10] - Jumlah baris default per halaman
 * @param {number[]} [options.options=[10, 50, 100]] - Array pilihan rowsPerPageOptions
 * @param {string} [options.label='data'] - Label entitas data (misal: 'siswa', 'guru', 'soal')
 * @param {string} [options.reportTemplate] - Kustom template laporan halaman
 * @param {string} [options.template] - Kustom paginator template
 * @returns {Object} Props siap pakai untuk v-bind pada DataTable
 */
export function getPaginationProps(options = {}) {
    const rows = options.rows ?? DEFAULT_PAGE_SIZE;
    const rowsPerPageOptions = options.options ?? options.rowsPerPageOptions ?? DEFAULT_ROWS_PER_PAGE_OPTIONS;
    const label = options.label ? ` ${options.label}` : ' data';
    const currentPageReportTemplate = options.reportTemplate ?? `Menampilkan {first} s.d {last} dari {totalRecords}${label}`;
    const paginatorTemplate = options.template ?? options.paginatorTemplate ?? DEFAULT_PAGINATOR_TEMPLATE;

    return {
        paginator: true,
        rows,
        rowsPerPageOptions,
        paginatorTemplate,
        currentPageReportTemplate,
    };
}

/**
 * Composable reactive untuk komponen yang membutuhkan kontrol state pagination tersendiri.
 * 
 * @param {Object} [options={}] - Opsi awal
 * @returns {Object} State dan props pagination reactive
 */
export function usePagination(options = {}) {
    const rows = ref(options.rows ?? DEFAULT_PAGE_SIZE);
    const rowsPerPageOptions = ref(options.options ?? DEFAULT_ROWS_PER_PAGE_OPTIONS);
    const label = ref(options.label || 'data');

    const paginationProps = computed(() => getPaginationProps({
        rows: rows.value,
        options: rowsPerPageOptions.value,
        label: label.value,
        ...options,
    }));

    return {
        rows,
        rowsPerPageOptions,
        label,
        paginationProps,
        getPaginationProps,
    };
}
