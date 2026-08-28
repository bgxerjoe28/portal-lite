<template>
    <component :is="isMobile ? MobileLayout : AppLayout" title="Pusat Izin Siswa">
        <div :class="isMobile ? 'p-2' : 'card border-0 shadow-sm'">
            
            <!-- Baris Judul -->
            <div class="flex flex-column sm:flex-row sm:justify-content-between sm:align-items-center gap-3 mb-4">
                <h2 class="text-xl md:text-2xl font-bold m-0 flex align-items-center">
                    <i class="pi pi-id-card text-primary mr-2 text-xl md:text-2xl"></i>
                    Pusat Izin Siswa
                </h2>
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button 
                        label="Tarik Google Sheet" 
                        icon="pi pi-cloud-download" 
                        severity="success" 
                        size="small" 
                        raised 
                        @click="showSheetModal = true" 
                    />
                    <Link :href="route('permits.frequency')" class="flex-1 sm:flex-none">
                        <Button :label="isMobile ? 'Rekap' : 'Rekap Frekuensi'" icon="pi pi-chart-bar" severity="info" text class="w-full" />
                    </Link>
                    <Button icon="pi pi-trash" severity="secondary" outlined size="small" @click="router.get(route('permits.trash'))" label="Sampah" />
                </div>
            </div>

            <!-- Form Pencatatan Izin -->
            <Tabs value="0" class="mb-4 shadow-2 border-round-lg">
                <TabList>
                    <Tab value="0"><i class="pi pi-users mr-2"></i>Input Multi Siswa</Tab>
                    <Tab value="1"><i class="pi pi-list mr-2"></i>Input Baris (Massal)</Tab>
                </TabList>
                <TabPanels class="p-0 border-top-1 surface-border">
                    <TabPanel value="0">
                        <div class="surface-card p-3 md:p-4 border-round-bottom-lg border-left-3 border-primary">
                            <div class="grid align-items-end gap-3" :class="{'p-fluid': isMobile}">
                                <div class="col-12 md:col-3">
                                    <label class="block mb-2 font-semibold text-700">Cari Siswa</label>
                                    <AutoComplete 
                                        v-model="form.selected_students" 
                                        :suggestions="studentOptions" 
                                        optionLabel="full_name" 
                                        placeholder="Ketik nama siswa..." 
                                        multiple
                                        class="w-full"
                                        inputClass="w-full"
                                        @complete="searchStudents"
                                    />
                                </div>
                                <div class="col-12 md:col-3">
                                    <label class="block mb-3 font-semibold text-700">Rentang Tanggal</label>
                                    <DatePicker 
                                        v-model="form.date_range" 
                                        selectionMode="range" 
                                        :manualInput="false"
                                        placeholder="Pilih Tanggal Mulai - Selesai" 
                                        class="w-full"
                                        showIcon
                                    />
                                </div>
                                <div class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Jenis Izin</label>
                                    <Select 
                                        v-model="form.permit_type" 
                                        :options="statusOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        class="w-full"
                                    />
                                </div>
                                <div class="col-12 md:col-3">
                                    <label class="block mb-2 font-semibold text-700">Keterangan</label>
                                    <InputText v-model="form.reason" placeholder="Contoh: Mengikuti Lomba" class="w-full" />
                                </div>

                                <div v-if="form.permit_type === 'D'" class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Jam Mulai</label>
                                    <Select 
                                        v-model="form.start_slot" 
                                        :options="slotOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        placeholder="Full Day"
                                        showClear
                                        class="w-full"
                                    />
                                </div>
                                
                                <div v-if="form.permit_type === 'D'" class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Jam Selesai</label>
                                    <Select 
                                        v-model="form.end_slot" 
                                        :options="slotOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        placeholder="Full Day"
                                        showClear
                                        class="w-full"
                                    />
                                </div>
                                
                                <div class="col-12 md:col-2">
                                    <Button label="Simpan Izin" icon="pi pi-check" class="w-full font-semibold" severity="primary" @click="submit" :loading="form.processing" />
                                </div>
                            </div>                
                        </div>
                    </TabPanel>
                    
                    <TabPanel value="1">
                        <div class="surface-card p-3 md:p-4 border-round-bottom-lg border-left-3 border-teal-500">
                            <div class="flex justify-content-between align-items-center mb-4">
                                <div class="flex flex-column">
                                    <label class="block mb-2 font-semibold text-700">Pilih Tanggal Berlaku</label>
                                    <DatePicker 
                                        v-model="bulkForm.date_range" 
                                        :manualInput="false"
                                        selectionMode="range"
                                        placeholder="Tanggal Izin" 
                                        class="w-20rem"
                                        showIcon
                                    />
                                </div>
                                <Button label="Simpan Massal" icon="pi pi-check" severity="teal" @click="submitBulk" :loading="bulkForm.processing" />
                            </div>

                            <div v-for="(row, index) in bulkForm.rows" :key="row.id" class="grid align-items-end gap-2 mb-3 pb-3 border-bottom-1 surface-border" :class="{'p-fluid': isMobile}">
                                <div class="col-12 md:col-3">
                                    <label class="block mb-2 font-semibold text-700">Siswa</label>
                                    <AutoComplete 
                                        v-model="row.selected_student" 
                                        :suggestions="studentOptions" 
                                        optionLabel="full_name" 
                                        placeholder="Cari Siswa..." 
                                        class="w-full"
                                        :class="{'p-invalid': row.error}"
                                        inputClass="w-full"
                                        @complete="searchStudents"
                                    />
                                    <small v-if="row.error" class="p-error">Wajib dipilih</small>
                                </div>
                                
                                <div class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Jenis Izin</label>
                                    <Select 
                                        v-model="row.permit_type" 
                                        :options="statusOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        class="w-full"
                                    />
                                </div>

                                <div v-if="row.permit_type === 'D'" class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Mulai</label>
                                    <Select 
                                        v-model="row.start_slot" 
                                        :options="slotOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        placeholder="Full Day"
                                        showClear
                                        class="w-full"
                                    />
                                </div>
                                
                                <div v-if="row.permit_type === 'D'" class="col-12 md:col-2">
                                    <label class="block mb-2 font-semibold text-700">Selesai</label>
                                    <Select 
                                        v-model="row.end_slot" 
                                        :options="slotOptions" 
                                        optionLabel="label" 
                                        optionValue="value" 
                                        placeholder="Full Day"
                                        showClear
                                        class="w-full"
                                    />
                                </div>
                                
                                <div class="col-12 md:col-3">
                                    <label class="block mb-2 font-semibold text-700">Keterangan</label>
                                    <InputText v-model="row.reason" placeholder="Keterangan" class="w-full" />
                                </div>
                                
                                <div class="col-12 md:col-1 flex align-items-center">
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeBulkRow(index)" v-if="bulkForm.rows.length > 1" />
                                </div>
                            </div>
                            
                            <div class="flex justify-content-between mt-3">
                                <Button label="Tambah Baris" icon="pi pi-plus" severity="secondary" outlined @click="addBulkRow" />
                                <Button label="Simpan Massal" icon="pi pi-check" severity="teal" @click="submitBulk" :loading="bulkForm.processing" />
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- Baris Filter Tanggal --><!-- Baris Filter Tanggal -->
            <div class="flex justify-content-between align-items-center mb-3">
                <h3 class="text-lg font-bold m-0" :class="isMobile ? '' : 'hidden md:block'">
                    {{ filters.student_id ? 'Riwayat Izin Siswa' : 'Riwayat Izin Harian' }}
                </h3>
                <DatePicker
                    v-if="!filters.student_id"
                    v-model="filterDate"
                    dateFormat="dd MM yy"
                    showIcon
                    :class="isMobile ? 'w-full' : 'w-15rem'"
                    @date-select="handleDateChange"
                />
            </div>

            <!-- Info filter siswa jika sedang melihat riwayat siswa tertentu -->
            <div v-if="filters.student_id" class="p-3 mb-3 bg-blue-50 border-round flex align-items-center justify-content-between border-left-3 border-blue-500">
                <div class="flex align-items-center gap-2">
                    <i class="pi pi-user text-blue-600 text-xl"></i>
                    <div>
                        <span class="font-semibold text-900">Riwayat Izin: {{ filters.student_name }}</span>
                        <p class="text-xs text-600 m-0">Menampilkan semua riwayat izin pada tahun ajaran aktif</p>
                    </div>
                </div>
                <Button icon="pi pi-times" severity="secondary" rounded text @click="clearStudentFilter" />
            </div>

            <!-- TAMPILAN DESKTOP: DataTable -->
            <div class="hidden md:block">
                <DataTable :value="permits" stripedRows class="shadow-1">
                    <Column header="No." style="width: 60px" class="text-center">
                        <template #body="{ index }">
                            {{ index + 1 }}
                        </template>
                    </Column>
                    <Column header="Tanggal" style="width: 140px" v-if="filters.student_id">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ formatDateIndo(data.date) }}</span>
                        </template>
                    </Column>
                    <Column header="Nama Siswa">
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-semibold">{{ data.student.full_name }}</span>
                                <small class="text-500">NIS {{ data.student.nis }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Kelas" style="width: 120px">
                        <template #body="{ data }">
                            <Tag 
                                :value="data.student?.current_classroom?.name || '-'" 
                                severity="secondary" 
                                rounded
                            />
                        </template>
                    </Column>
                    <Column header="Status" style="width: 120px" class="text-center">
                        <template #body="{ data }">
                            <Tag :value="getLabel(data.permit_type)" :severity="getSeverity(data.permit_type)" />
                        </template>
                    </Column>
                    <Column field="reason" header="Keterangan">
                        <template #body="{ data }">
                            <div>{{ data.reason }}</div>
                            <small v-if="data.permit_type === 'D' && (data.start_slot || data.end_slot)" class="text-primary font-semibold">
                                Jam {{ data.start_slot || '1' }} - {{ data.end_slot || 'Akhir' }}
                            </small>
                        </template>
                    </Column>
                    
                    <Column header="Dicatat Oleh">
                        <template #body="{ data }">
                            <div class="flex flex-column">
                                <span class="font-semibold text-900">{{ data.display_name }}</span>
                                <small v-if="data.recorder?.teacher" class="text-500">
                                    NIP: {{ data.recorder.teacher.nip }}
                                </small>
                                <Tag v-else value="STAF" severity="secondary" class="text-xs w-fit" />
                            </div>
                        </template>
                    </Column>
                    <Column header="Aksi" style="width: 80px">
                        <template #body="{ data }">
                            <div class="flex justify-content-center gap-1">
                                <Button 
                                    icon="pi pi-print" 
                                    severity="info" 
                                    rounded 
                                    text
                                    v-tooltip.top="'Cetak Karcis'"
                                    @click="printPermit(data.id)" 
                                />
                                <Button 
                                    icon="pi pi-file-pdf" 
                                    severity="danger" 
                                    rounded 
                                    text
                                    v-tooltip.top="'Simpan PDF'"
                                    @click="downloadPdf(data.id)" 
                                />
                                <Button 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    text 
                                    rounded 
                                    @click="deletePermit(data.id)" 
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- TAMPILAN MOBILE: Card List -->
            <div class="flex flex-column gap-3 md:hidden">
                <div v-for="item in permits" :key="item.id" class="surface-card p-3 shadow-1 border-round-lg border-left-3" :class="'border-' + getSeverity(item.permit_type)">
                    <div class="flex justify-content-between align-items-start mb-2">
                        <div class="flex flex-column">
                            <span class="font-bold text-900">{{ item.student.full_name }}</span>
                            <small class="text-600">
                                {{ item.student?.current_classroom?.name || 'No Class' }}
                                <span v-if="filters.student_id" class="text-primary font-semibold block mt-1">
                                    <i class="pi pi-calendar text-xs mr-1"></i>{{ formatDateIndo(item.date) }}
                                </span>
                            </small>
                        </div>
                        <Tag :value="getLabel(item.permit_type)" :severity="getSeverity(item.permit_type)" />
                    </div>
                    
                    <div class="text-700 text-sm mb-3">
                        <strong>Ket:</strong> {{ item.reason }}
                        <div v-if="item.permit_type === 'D' && (item.start_slot || item.end_slot)" class="text-primary font-semibold mt-1">
                            Jam {{ item.start_slot || '1' }} - {{ item.end_slot || 'Akhir' }}
                        </div>
                    </div>
                    
                    <div class="flex justify-content-between align-items-center border-top-1 border-100 pt-2">
                        <span class="text-xs text-500">Oleh: {{ item.display_name }}</span>
                        <div class="flex gap-1">
                            <Button icon="pi pi-print" severity="info" text size="small" @click="printPermit(item.id)" />
                            <Button icon="pi pi-file-pdf" severity="danger" text size="small" @click="downloadPdf(item.id)" />
                            <Button icon="pi pi-trash" severity="danger" text size="small" @click="deletePermit(item.id)" />
                        </div>
                    </div>
                </div>
                <div v-if="permits.length === 0" class="text-center p-5 text-500">
                    Tidak ada data izin siswa.
                </div>
            </div>

            <!-- MODAL SINKRONISASI GOOGLE SHEET -->
            <Dialog 
                v-model:visible="showSheetModal" 
                header="Tarik & Sinkronkan Izin dari Google Sheets" 
                modal 
                :style="{ width: '92vw', maxWidth: '680px' }"
            >
                <div class="py-2">
                    <div class="p-3 bg-green-50 border-round-lg border-1 border-green-200 mb-3 flex align-items-start gap-2">
                        <i class="pi pi-google text-green-600 text-xl mt-1"></i>
                        <div class="text-xs text-green-900 line-height-3">
                            <strong>Sinkronisasi Spreadsheet:</strong> Sistem akan membaca data izin dari link Google Spreadsheet publik Anda, mencocokkan nama siswa & kelas, dan langsung menyimpannya ke database izin portal.
                        </div>
                    </div>

                    <div class="field mb-3">
                        <label class="block text-xs font-bold text-700 uppercase mb-1">
                            URL / Link Google Spreadsheet
                        </label>
                        <InputText 
                            v-model="sheetForm.sheet_url" 
                            placeholder="https://docs.google.com/spreadsheets/d/..." 
                            class="w-full"
                        />
                        <small class="text-500 text-xs">Pastikan akses share Google Sheet berstatus <em>"Anyone with the link can view"</em>.</small>
                    </div>

                    <div class="field-checkbox mb-4 flex align-items-center gap-2">
                        <Checkbox v-model="sheetForm.only_unchecked" :binary="true" inputId="onlyUnchecked" />
                        <label for="onlyUnchecked" class="text-xs text-700 font-semibold cursor-pointer">
                            Hanya proses baris yang kolom <strong>"Cek Input Portal"</strong> bernilai <em>FALSE</em> (Belum Diinput)
                        </label>
                    </div>

                    <!-- HASIL SINKRONISASI JIKA ADA DATA YANG TIDAK COCOK -->
                    <div v-if="$page.props.flash?.sheetSyncResult" class="mb-4">
                        <div class="surface-100 p-3 border-round-lg border-1 surface-border">
                            <div class="font-bold text-sm text-900 mb-2">Ringkasan Tarik Data Terakhir:</div>
                            <div class="flex gap-3 text-xs mb-2">
                                <span class="text-green-700 font-bold">✅ {{ $page.props.flash.sheetSyncResult.imported_count }} Masuk</span>
                                <span class="text-500">⏭️ {{ $page.props.flash.sheetSyncResult.skipped_count }} Dilewati</span>
                                <span v-if="$page.props.flash.sheetSyncResult.unmatched_rows?.length" class="text-red-600 font-bold">
                                    ⚠️ {{ $page.props.flash.sheetSyncResult.unmatched_rows.length }} Gagal Dicocokkan
                                </span>
                            </div>

                            <div v-if="$page.props.flash.sheetSyncResult.unmatched_rows?.length" class="mt-2 text-xs text-red-700 max-h-10rem overflow-y-auto bg-red-50 p-2 border-round">
                                <div class="font-bold mb-1">Baris yang tidak cocok:</div>
                                <div v-for="u in $page.props.flash.sheetSyncResult.unmatched_rows" :key="u.row" class="mb-1">
                                    • Baris {{ u.row }}: <strong>{{ u.name }}</strong> (Kelas: {{ u.class || '-' }}) — {{ u.reason }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANDUAN OTOMATISASI GOOGLE APPS SCRIPT (2 ARAH) -->
                    <div class="border-top-1 surface-border pt-3">
                        <div class="flex justify-content-between align-items-center mb-2 cursor-pointer" @click="showGasGuide = !showGasGuide">
                            <span class="text-xs font-bold text-primary flex align-items-center gap-1">
                                <i class="pi pi-code"></i>
                                Panduan Google Apps Script (Centang Otomatis 2 Arah di Spreadsheet)
                            </span>
                            <i :class="showGasGuide ? 'pi pi-chevron-up text-primary' : 'pi pi-chevron-down text-primary'"></i>
                        </div>

                        <div v-if="showGasGuide" class="surface-50 p-3 border-round-lg text-xs text-700 line-height-3">
                            <p class="m-0 mb-2">Agar Google Spreadsheet Anda bisa <strong>otomatis mengirim ke portal dan otomatis mencentang kolom "Cek Input Portal" = TRUE</strong> saat tombol diklik di Spreadsheet:</p>
                            <ol class="m-0 pl-3 mb-3">
                                <li>Buka Google Spreadsheet Anda.</li>
                                <li>Klik menu <strong>Extensions (Ekstensi) &gt; Apps Script</strong>.</li>
                                <li>Hapus semua kode lama dan paste kode di bawah ini, lalu simpan (Ctrl+S).</li>
                                <li>Refresh Google Sheet, menu baru <strong>"Portal SMA"</strong> akan muncul di bilah atas spreadsheet!</li>
                            </ol>

                            <div class="flex justify-content-end mb-2">
                                <Button 
                                    :label="isCopied ? 'Tersalin!' : 'Salin Kode Script'" 
                                    :icon="isCopied ? 'pi pi-check' : 'pi pi-copy'" 
                                    size="small" 
                                    :severity="isCopied ? 'success' : 'secondary'"
                                    class="text-xs py-1 px-3"
                                    @click="copyGasCode" 
                                />
                            </div>
                            <pre class="bg-900 text-green-400 p-3 border-round text-xs font-mono overflow-x-auto m-0" style="max-height: 180px;">{{ gasScriptCode }}</pre>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-content-end gap-2">
                        <Button label="Tutup" severity="secondary" size="small" @click="showSheetModal = false" />
                        <Button 
                            label="Mulai Tarik Data" 
                            icon="pi pi-cloud-download" 
                            severity="success" 
                            size="small" 
                            :loading="sheetForm.processing" 
                            @click="submitSheetSync" 
                        />
                    </div>
                </template>
            </Dialog>

        </div>
    </component>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Button from 'primevue/button';
import axios from 'axios';
import AutoComplete from 'primevue/autocomplete';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';

const props = defineProps({ permits: Array, filters: Object, savedSheetUrl: String });
const confirm = useConfirm();
const filterDate = ref(new Date(props.filters.date));
const studentOptions = ref([]);

// Screen size detection
const isMobile = ref(true); // Guru selalu menggunakan layout mobile

const checkScreenSize = () => {
    isMobile.value = true;
};

onMounted(() => {
    checkScreenSize();
});



const form = useForm({
    student_id: null,
    selected_students: [],
    date_range: null,
    permit_type: 'I',
    reason: '',
    start_slot: null,
    end_slot: null,
});

const slotOptions = Array.from({ length: 10 }, (_, i) => ({ label: `Jam ${i + 1}`, value: i + 1 }));

const searchStudents = async (event) => {
    if (!event.query.trim().length) return;
    
    try {
        const res = await axios.get(route('permits.search-students'), {
            params: { q: event.query }
        });
        studentOptions.value = res.data;
    } catch (e) {
        console.error("Gagal memuat siswa", e);
    }
};

const statusOptions = [
    { label: 'Sakit', value: 'S' },
    { label: 'Izin', value: 'I' },
    { label: 'Dispen', value: 'D' },
    { label: 'Tanpa Keterangan', value: 'A' },
    { label: 'Terlambat', value: 'T' },
];


const bulkForm = useForm({
    date_range: null,
    rows: [
        { id: Date.now(), selected_student: null, permit_type: 'I', start_slot: null, end_slot: null, reason: '', error: false }
    ]
});

const addBulkRow = () => {
    bulkForm.rows.push({ id: Date.now(), selected_student: null, permit_type: 'I', start_slot: null, end_slot: null, reason: '', error: false });
};

const removeBulkRow = (index) => {
    bulkForm.rows.splice(index, 1);
};

const submitBulk = () => {
    let hasError = false;
    bulkForm.rows.forEach(row => {
        row.student_id = row.selected_student?.id;
        row.error = !row.student_id;
        if(row.error) hasError = true;
    });

    if (hasError) return alert('Pilih siswa pada baris yang ditandai merah.');
    if (!bulkForm.date_range) return alert('Pilih tanggal berlaku.');

    const formatDate = (date) => {
        if (!date) return null;
        const d = new Date(date);
        return d.toLocaleDateString('en-CA');
    };

    let dates = bulkForm.date_range.filter(d => d).map(formatDate);

    const payload = {
        date_range: dates,
        rows: bulkForm.rows.map(r => ({
            student_id: r.student_id,
            permit_type: r.permit_type,
            start_slot: r.start_slot,
            end_slot: r.end_slot,
            reason: r.reason
        }))
    };

    router.post(route('permits.store-bulk'), payload, {
        onSuccess: () => {
            bulkForm.reset('date_range');
            bulkForm.rows = [{ id: Date.now(), selected_student: null, permit_type: 'I', start_slot: null, end_slot: null, reason: '', error: false }];
        }
    });
};

const submit = () => {
    if (!form.selected_students || form.selected_students.length === 0) return alert('Pilih minimal satu siswa terlebih dahulu');
    if (!form.date_range || !form.date_range[0]) return alert('Pilih rentang tanggal');

    const formatDate = (date) => {
        if (!date) return null;
        const d = new Date(date);
        return d.toLocaleDateString('en-CA');
    };

    const payload = {
        ...form.data(),
        student_ids: form.selected_students.map(s => s.id),
        date_range: [
            formatDate(form.date_range[0]),
            formatDate(form.date_range[1])
        ]
    };

    router.post(route('permits.store'), payload, {
        onSuccess: () => {
            form.reset('selected_students', 'student_id', 'reason', 'date_range', 'start_slot', 'end_slot');
        }
    });
};

const handleDateChange = (val) => {
    const d = new Date(val).toLocaleDateString('en-CA');
    router.get(route('permits.index'), { date: d });
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const clearStudentFilter = () => {
    router.get(route('permits.index'));
};

const getLabel = (t) => ({ 'S': 'SAKIT', 'I': 'IZIN', 'D': 'DISPEN', 'A': 'TANPA KETERANGAN', 'T': 'TERLAMBAT' }[t]);
const getSeverity = (t) => {
    const map = { 'S': 'info', 'I': 'warn', 'D': 'help', 'A': 'danger', 'T': 'primary' };
    return map[t] || 'secondary';
};

const deletePermit = (id) => {
    confirm.require({
        message: 'Hapus data izin ini?',
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Hapus',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('permits.destroy', id));
        }
    });
};

const printPermit = (id) => {
    window.open(route('lates.print', id), '_blank');
};

const downloadPdf = (id) => {
    window.location.href = route('lates.pdf', id);
};

// Google Sheet Sync State & Actions
const showSheetModal = ref(false);
const showGasGuide = ref(false);
const isCopied = ref(false);

const sheetForm = useForm({
    sheet_url: props.savedSheetUrl || 'https://docs.google.com/spreadsheets/d/1St-UOseLrUUg6MIUUS64b3CMynAtaK0BvxxbftfzXrM/edit?gid=0',
    only_unchecked: true,
});

const gasScriptCode = `/**
 * GOOGLE APPS SCRIPT: SINKRONISASI IZIN SISWA KE PORTAL SMA
 * 
 * 1. Buka Google Sheet Anda
 * 2. Menu Extensions > Apps Script
 * 3. Hapus isi lama, paste kode ini, lalu Simpan (Ctrl+S)
 * 4. Refresh Google Sheet, menu baru "Portal SMA" akan muncul!
 */

const PORTAL_SYNC_URL = "${typeof window !== 'undefined' ? window.location.origin : ''}/api/permits/sync-sheet";

function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('🚀 Portal SMA')
    .addItem('📥 Kirim Izin Baru ke Portal', 'syncUncheckedRows')
    .addItem('🔄 Sinkronkan Seluruh Data', 'syncAllRows')
    .addToUi();
}

function syncUncheckedRows() {
  syncToPortal(true);
}

function syncAllRows() {
  syncToPortal(false);
}

function syncToPortal(onlyUnchecked) {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const data = sheet.getDataRange().getValues();
  if (data.length < 2) {
    SpreadsheetApp.getUi().alert('Data spreadsheet masih kosong.');
    return;
  }

  const headers = data[0].map(h => String(h).toLowerCase().trim());
  const colCekPortalIdx = headers.indexOf('cek input portal');

  if (colCekPortalIdx === -1) {
    SpreadsheetApp.getUi().alert('Error: Kolom "Cek Input Portal" tidak ditemukan di baris header.');
    return;
  }

  const sheetUrl = SpreadsheetApp.getActiveSpreadsheet().getUrl();
  const payload = {
    sheet_url: sheetUrl,
    only_unchecked: onlyUnchecked
  };

  const options = {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };

  try {
    const response = UrlFetchApp.fetch(PORTAL_SYNC_URL, options);
    const result = JSON.parse(response.getContentText());

    if (result.status === 'success') {
      const syncedRowNumbers = result.data.synced_row_numbers || [];
      
      syncedRowNumbers.forEach(rowNum => {
        sheet.getRange(rowNum, colCekPortalIdx + 1).setValue(true);
      });

      let msg = '✅ Berhasil! ' + result.data.imported_count + ' data izin masuk ke Portal SMA.';
      if (result.data.skipped_count > 0) {
        msg += '\\n(' + result.data.skipped_count + ' data dilewati karena sudah bernilai TRUE).';
      }
      if (result.data.unmatched_rows && result.data.unmatched_rows.length > 0) {
        msg += '\\n\\n⚠️ ' + result.data.unmatched_rows.length + ' baris gagal dicocokkan:';
        result.data.unmatched_rows.slice(0, 5).forEach(u => {
          msg += '\\n- Baris ' + u.row + ': ' + u.name + ' (' + u.reason + ')';
        });
      }
      SpreadsheetApp.getUi().alert(msg);
    } else {
      SpreadsheetApp.getUi().alert('❌ Gagal: ' + (result.message || 'Terjadi kesalahan'));
    }
  } catch (err) {
    SpreadsheetApp.getUi().alert('❌ Error koneksi ke Portal: ' + err.toString());
  }
}`;

const copyGasCode = () => {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(gasScriptCode);
        isCopied.value = true;
        setTimeout(() => { isCopied.value = false; }, 2500);
    }
};

const submitSheetSync = () => {
    sheetForm.post(route('permits.sync-google-sheet'), {
        preserveScroll: true,
        onSuccess: () => {
            // Done
        }
    });
};
</script>

<style scoped>
/* Mewarnai tombol aktif pada SelectButton */
:deep(.custom-selectbutton .p-button.p-highlight) {
    background: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}

/* Warna khusus */
:deep(.p-selectbutton .p-button.p-highlight[aria-label="Sakit"]) { background-color: #3B82F6 !important; }
:deep(.p-selectbutton .p-button.p-highlight[aria-label="Izin"]) { background-color: #F59E0B !important; }
:deep(.p-selectbutton .p-button.p-highlight[aria-label="Dispen"]) { background-color: #A855F7 !important; }
:deep(.p-selectbutton .p-button.p-highlight[aria-label="Tanpa Keterangan"]) { background-color: #EF4444 !important; }

/* Custom warna tag untuk sinkronisasi dengan border card mobile */
.border-info { border-color: #3B82F6 !important; }
.border-warn { border-color: #F59E0B !important; }
.border-help { border-color: #A855F7 !important; }
.border-danger { border-color: #EF4444 !important; }
.border-primary { border-color: var(--primary-color) !important; }

@media screen and (max-width: 768px) {
    :deep(.p-autocomplete-input) {
        width: 100%;
    }
}
</style>