<template>
    <AppLayout>
        <Head title="Data Siswa" />

        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex flex-column lg:flex-row lg:justify-content-between lg:align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">
                        {{ filters.trash === 'true' ? 'Arsip Siswa' : 'Data Siswa' }}
                    </h2>
                    <span class="text-600 text-sm">
                        Kelola data siswa dan riwayat akademik
                    </span>
                </div>
                
                <div class="flex flex-wrap gap-2 align-items-center">
                    <Select 
                        v-model="selectedStatus" 
                        :options="statusOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Status Siswa" 
                        class="w-10rem"
                        @change="applyFilters"
                    />

                    <MultiSelect 
                        v-model="selectedClass" 
                        :options="classrooms" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Filter Kelas" 
                        display="chip"
                        :maxSelectedLabels="3"
                        class="w-18rem"
                        @change="handleFilterChange"
                        v-if="selectedStatus === 'aktif'"
                    />
                    <Select 
                        v-model="selectedReligion" 
                        :options="religions" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Agama" 
                        showClear
                        class="w-8rem"
                        @change="applyFilters"
                    />

                    <Button 
                        :icon="filters.trash === 'true' ? 'pi pi-list' : 'pi pi-trash'" 
                        :severity="filters.trash === 'true' ? 'primary' : 'secondary'" 
                        outlined 
                        @click="toggleTrash" 
                        v-tooltip.top="'Arsip / Sampah'"
                    />

                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Nama / NIS..." class="w-15rem" />
                    </IconField>
                    <Button label="Data Utama" icon="pi pi-file-excel" severity="success" @click="openImportModal" v-if="filters.trash !== 'true'" />
                    <Button label="Update Data" icon="pi pi-file-excel" severity="success" @click="openImportUpdateModal" v-if="filters.trash !== 'true'" />
                    <Button label="Eksport Excel" icon="pi pi-download" severity="success" @click="exportExcel" v-if="filters.trash !== 'true'" />
                    <Button label="Bulk Edit NIS (Excel)" icon="pi pi-pencil" severity="warning" @click="openBulkEditModal" v-if="filters.trash !== 'true'" />
                    <Button label="Generate NIS" icon="pi pi-id-card" severity="help" @click="confirmGenerateNis" v-if="filters.trash !== 'true'" />
                    <Button label="Tambah" icon="pi pi-plus" @click="openCreateModal" v-if="filters.trash !== 'true'" />
                </div>
            </div>
        </div>
        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
                <div class="datatable-scroll">
                    <div class="datatable-container">
                    <DataTable
                        :value="students.data"
                        v-model:selection="selectedStudents"
                        dataKey="id"
                        stripedRows
                        scrollable
                        scrollHeight="600px"
                        responsiveLayout="scroll"
                        tableStyle="min-width:120rem"
                    >

                        <template #empty>
                            Data siswa tidak ditemukan.
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem" frozen></Column>

                        <Column header="No" style="width:5rem" frozen>
                            <template #body="{ index }">
                                {{ (students.from || 1) + index }}
                            </template>
                        </Column>

                        <Column header="Kelas" style="width:10rem">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.classrooms.length > 0 ? data.classrooms[0].name : 'Belum Masuk Kelas'"
                                    :severity="data.classrooms.length > 0 ? 'info' : 'warning'"
                                />
                            </template>
                        </Column>

                        <Column field="nis" header="NIS" style="width:10rem" />
                        <Column field="nisn" header="NISN" style="width:10rem" />

                        <Column field="full_name" header="Nama Siswa" frozen style="width:20rem">
                            <template #body="{ data }">
                                <span class="font-bold">{{ data.full_name }}</span>
                                <div class="text-500 text-sm">
                                    {{ data.gender ? 'Laki-laki' : 'Perempuan' }}
                                </div>
                            </template>
                        </Column>

                        <Column header="Data Keluarga" style="width:25rem">
                            <template #body="{ data }">
                                <div class="text-sm">
                                    <div><b>Ayah:</b> {{ data.father_name || '-' }}</div>
                                    <div><b>Ibu:</b> {{ data.mother_name || '-' }}</div>
                                    <div class="mt-1"><b>Wali:</b> {{ data.guardian_name || '-' }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Agama" style="width:10rem">
                            <template #body="{ data }">
                                {{ data.religion?.name || '-' }}
                            </template>
                        </Column>

                        <Column header="Akun Login" style="width:20rem">
                            <template #body="{ data }">
                                <code class="text-primary">{{ data.user?.email }}</code>
                            </template>
                        </Column>

                        <Column header="Aksi" style="width:10rem" alignFrozen="right" frozen>
                            <template #body="{ data }">
                                <div class="flex gap-2" v-if="filters.trash === 'true'">
                                    <Button icon="pi pi-refresh" severity="success" text rounded @click="confirmRestore(data)" v-tooltip.top="'Pulihkan Siswa'" />
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmForceDelete(data)" v-tooltip.top="'Hapus Permanen'" />
                                </div>
                                <div class="flex gap-2" v-else>
                                    <Button icon="pi pi-pencil" severity="warning" text rounded @click="openEditModal(data)" v-tooltip.top="'Edit Siswa'" />
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" v-tooltip.top="'Arsipkan Siswa'" />
                                </div>
                            </template>
                        </Column>

                    </DataTable>
                </div>
                </div>
                <div class="flex align-items-center justify-content-between mt-4">
                    <div class="flex align-items-center gap-2">
                        <span class="text-sm text-500">Tampilkan</span>
                        <Select 
                            v-model="selectedPerPage" 
                            :options="perPageOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                            class="w-8rem"
                            @change="applyFilters"
                        />
                        <span class="text-sm text-500">Data</span>
                    </div>
                    <Pagination :links="students.links" />
                </div>

            </div>

        <Dialog v-model:visible="displayModal" :header="isEditing ? 'Edit Siswa' : 'Tambah Siswa'" :modal="true" :style="{ width: '850px' }">
            <form @submit.prevent="submitForm">
                
<Tabs value="0">
    <TabList>
        <Tab value="0">Akademik</Tab>
        <Tab value="1">Data Pribadi</Tab>
        <Tab value="2">Orang Tua & Wali</Tab>
        <Tab value="3">Periodik</Tab>
        <Tab value="4">Akun</Tab>
    </TabList>
    <TabPanels>

                    <!-- TAB 1: AKADEMIK -->
                    <TabPanel value="0" >
                        <div class="formgrid grid">
                            <div class="field col-12">
                                <label class="font-semibold mb-2 block">Nama Lengkap <span class="text-red-500">*</span></label>
                                <InputText v-model="form.full_name" class="w-full" :class="{'p-invalid': form.errors.full_name}" required />
                                <small class="p-error">{{ form.errors.full_name }}</small>
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">NIS <span class="text-red-500">*</span></label>
                                <InputText v-model="form.nis" class="w-full" :class="{'p-invalid': form.errors.nis}" required />
                                <small class="p-error">{{ form.errors.nis }}</small>
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">NISN <span class="text-red-500">*</span></label>
                                <InputText v-model="form.nisn" class="w-full" :class="{'p-invalid': form.errors.nisn}" required />
                                <small class="p-error">{{ form.errors.nisn }}</small>
                            </div>
                            <div class="field col-12" v-if="!isEditing">
                                <label class="font-semibold mb-2 block">Pilih Kelas Awal</label>
                                <Select 
                                    v-model="form.classroom_id" 
                                    :options="classrooms" 
                                    optionLabel="name" 
                                    optionValue="id" 
                                    placeholder="Pilih Kelas" 
                                    class="w-full"
                                />
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: DATA PRIBADI -->
                    <TabPanel value="1" >
                        <div class="formgrid grid">
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">NIK <span class="text-red-500">*</span></label>
                                <InputText v-model="form.nik" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">No. KK <span class="text-red-500">*</span></label>
                                <InputText v-model="form.no_kk" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">No. Registrasi Akta Lahir</label>
                                <InputText v-model="form.akta_no" class="w-full" />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Kewarganegaraan <span class="text-red-500">*</span></label>
                                <Select v-model="form.citizenship" :options="['WNI', 'WNA']" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Tempat Lahir <span class="text-red-500">*</span></label>
                                <InputText v-model="form.birth_place" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Tanggal Lahir <span class="text-red-500">*</span></label>
                                <InputText type="date" v-model="form.birth_date" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Agama & Kepercayaan <span class="text-red-500">*</span></label>
                                <Select 
                                    v-model="form.religion_id" 
                                    :options="religions" 
                                    optionLabel="name" 
                                    optionValue="id" 
                                    placeholder="Pilih Agama" 
                                    class="w-full" 
                                    required
                                />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">No. HP / WA <span class="text-red-500">*</span></label>
                                <InputText v-model="form.phone" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.gender" :value="true" inputId="gender-l" />
                                        <label for="gender-l" class="ml-2">Laki-laki</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.gender" :value="false" inputId="gender-p" />
                                        <label for="gender-p" class="ml-2">Perempuan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Berkebutuhan Khusus</label>
                                <InputText v-model="form.special_needs" class="w-full" />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Anak Ke- <span class="text-red-500">*</span></label>
                                <InputText v-model="form.child_order" type="number" min="1" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Tempat Tinggal <span class="text-red-500">*</span></label>
                                <InputText v-model="form.residence_type" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Moda Transportasi <span class="text-red-500">*</span></label>
                                <InputText v-model="form.transportation" class="w-full" required />
                            </div>
                            
                            <!-- ASAL SEKOLAH -->
                            <div class="field col-12">
                                <h4 class="text-blue-800 font-bold border-bottom-1 border-100 pb-1 mt-2 mb-2">Asal Sekolah (SMP/MTS)</h4>
                            </div>
                            <div class="field col-12 md:col-3">
                                <label class="font-semibold mb-2 block">Jenjang Sekolah <span class="text-red-500">*</span></label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.prev_school_type" value="SMP" inputId="prev-smp" />
                                        <label for="prev-smp" class="ml-2">SMP</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.prev_school_type" value="MTS" inputId="prev-mts" />
                                        <label for="prev-mts" class="ml-2">MTS</label>
                                    </div>
                                </div>
                            </div>
                            <div class="field col-12 md:col-3">
                                <label class="font-semibold mb-2 block">Status Sekolah <span class="text-red-500">*</span></label>
                                <div class="flex gap-4 mt-2">
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.prev_school_status" value="Negeri" inputId="prev-negeri" />
                                        <label for="prev-negeri" class="ml-2">Negeri</label>
                                    </div>
                                    <div class="flex align-items-center">
                                        <RadioButton v-model="form.prev_school_status" value="Swasta" inputId="prev-swasta" />
                                        <label for="prev-swasta" class="ml-2">Swasta</label>
                                    </div>
                                </div>
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Nama Sekolah Asal <span class="text-red-500">*</span></label>
                                <InputText v-model="form.prev_school_name" class="w-full" required />
                            </div>

                            <!-- ALAMAT LENGKAP -->
                            <div class="field col-12">
                                <h4 class="text-blue-800 font-bold border-bottom-1 border-100 pb-1 mt-2 mb-2">Alamat Lengkap</h4>
                            </div>
                            <div class="field col-12">
                                <label class="font-semibold mb-2 block">Alamat Jalan <span class="text-red-500">*</span></label>
                                <Textarea v-model="form.address" rows="2" class="w-full" required autoResize />
                            </div>
                            <div class="field col-6 md:col-3">
                                <label class="font-semibold mb-2 block">RT <span class="text-red-500">*</span></label>
                                <InputText v-model="form.rt" class="w-full" required />
                            </div>
                            <div class="field col-6 md:col-3">
                                <label class="font-semibold mb-2 block">RW <span class="text-red-500">*</span></label>
                                <InputText v-model="form.rw" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Dusun</label>
                                <InputText v-model="form.dusun" class="w-full" />
                            </div>
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Kelurahan <span class="text-red-500">*</span></label>
                                <InputText v-model="form.kelurahan" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Kecamatan <span class="text-red-500">*</span></label>
                                <InputText v-model="form.kecamatan" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Kode Pos <span class="text-red-500">*</span></label>
                                <InputText v-model="form.postal_code" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Lintang (Latitude)</label>
                                <InputText v-model="form.latitude" class="w-full" />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Bujur (Longitude)</label>
                                <InputText v-model="form.longitude" class="w-full" />
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 3: ORANG TUA & WALI -->
                    <TabPanel value="2" >
                        <div class="formgrid grid">
                            <!-- AYAH -->
                            <div class="field col-12 md:col-6">
                                <h4 class="text-blue-800 font-bold border-bottom-1 border-100 pb-1 mb-3">DATA AYAH</h4>
                                <div class="field mb-3">
                                    <label class="font-semibold mb-2 block">Nama Lengkap Ayah <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_name" class="w-full" required />
                                </div>
                                <div class="field mb-3 flex align-items-center gap-2">
                                    <Checkbox v-model="form.father_deceased" :binary="true" inputId="admin-f-deceased" />
                                    <label for="admin-f-deceased" class="font-semibold cursor-pointer">Sudah Meninggal</label>
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">NIK Ayah <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_nik" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">Tahun Lahir <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_birth_year" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">Pendidikan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_education" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">Pekerjaan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_job" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">Penghasilan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_income" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.father_deceased">
                                    <label class="font-semibold mb-2 block">No. WA Ayah <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.father_phone" class="w-full" required />
                                </div>
                            </div>

                            <!-- IBU -->
                            <div class="field col-12 md:col-6">
                                <h4 class="text-blue-800 font-bold border-bottom-1 border-100 pb-1 mb-3">DATA IBU</h4>
                                <div class="field mb-3">
                                    <label class="font-semibold mb-2 block">Nama Lengkap Ibu <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_name" class="w-full" required />
                                </div>
                                <div class="field mb-3 flex align-items-center gap-2">
                                    <Checkbox v-model="form.mother_deceased" :binary="true" inputId="admin-m-deceased" />
                                    <label for="admin-m-deceased" class="font-semibold cursor-pointer">Sudah Meninggal</label>
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">NIK Ibu <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_nik" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">Tahun Lahir <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_birth_year" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">Pendidikan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_education" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">Pekerjaan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_job" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">Penghasilan <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_income" class="w-full" required />
                                </div>
                                <div class="field mb-3" v-if="!form.mother_deceased">
                                    <label class="font-semibold mb-2 block">No. WA Ibu <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.mother_phone" class="w-full" required />
                                </div>
                            </div>

                            <!-- WALI -->
                            <div class="field col-12">
                                <h4 class="text-blue-800 font-bold border-bottom-1 border-100 pb-1 mt-3 mb-3">DATA WALI (OPSIONAL)</h4>
                                <div class="field mb-3 flex align-items-center gap-2">
                                    <Checkbox v-model="form.has_guardian" :binary="true" inputId="admin-has-g" />
                                    <label for="admin-has-g" class="font-semibold cursor-pointer">Siswa memiliki wali</label>
                                </div>
                                <div v-if="form.has_guardian" class="grid">
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">Nama Wali <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_name" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">NIK Wali <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_nik" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">Tahun Lahir <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_birth_year" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">Pendidikan <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_education" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">Pekerjaan <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_job" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">Penghasilan <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_income" class="w-full" required />
                                    </div>
                                    <div class="field col-12 md:col-6">
                                        <label class="font-semibold mb-2 block">No. WA Wali <span class="text-red-500">*</span></label>
                                        <InputText v-model="form.guardian_phone" class="w-full" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 4: PERIODIK -->
                    <TabPanel value="3" >
                        <div class="formgrid grid">
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                                <InputText v-model="form.height" type="number" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Berat Badan (kg) <span class="text-red-500">*</span></label>
                                <InputText v-model="form.weight" type="number" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-4">
                                <label class="font-semibold mb-2 block">Lingkar Kepala (cm) <span class="text-red-500">*</span></label>
                                <InputText v-model="form.head_circumference" type="number" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Jarak ke Sekolah (km) <span class="text-red-500">*</span></label>
                                <InputText v-model="form.distance_to_school_km" type="number" step="0.1" class="w-full" required />
                            </div>
                            <div class="field col-12 md:col-6">
                                <label class="font-semibold mb-2 block">Waktu Tempuh (menit) <span class="text-red-500">*</span></label>
                                <InputText v-model="form.travel_time_minutes" type="number" class="w-full" required />
                            </div>
                            <div class="field col-12">
                                <label class="font-semibold mb-2 block">Jumlah Saudara Kandung <span class="text-red-500">*</span></label>
                                <InputText v-model="form.sibling_count" type="number" class="w-full" required />
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 5: AKUN -->
                    <TabPanel value="4" >
                        <div class="field mb-3 mt-2">
                            <label class="font-semibold mb-2 block">Email Login <span class="text-red-500">*</span></label>
                            <InputText v-model="form.email" type="email" class="w-full" :class="{'p-invalid': form.errors.email}" required />
                            <small class="p-error block">{{ form.errors.email }}</small>
                        </div>
                        <div class="surface-ground p-3 border-round text-sm" v-if="!isEditing">
                            <i class="pi pi-info-circle mr-2 text-primary"></i>
                            Password default untuk siswa baru adalah: <b>siswa123</b>
                        </div>
                    </TabPanel>
                
    </TabPanels>
</Tabs>

                <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1 border-200">
                    <Button label="Batal" severity="secondary" text @click="displayModal = false" />
                    <Button :label="isEditing ? 'Simpan Perubahan' : 'Simpan Data'" type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    <Dialog v-model:visible="displayImportModal" header="Import Data Siswa" :modal="true" :style="{ width: '400px' }">
        <div class="flex flex-column gap-3">
            <p class="text-sm text-600">
                Pastikan nama kelas di Excel (jika ada) sesuai dengan data kelas di sistem. Password default siswa: <b>siswa123</b>.
            </p>
            
            <a :href="route('admin.students.template')" class="no-underline">
                <Button label="Download Template Excel" icon="pi pi-download" severity="secondary" outlined class="w-full" />
            </a>

            <Divider />

            <form @submit.prevent="submitImport">
                <div class="field">
                    <label class="font-medium mb-2 block">Pilih File Excel (.xlsx)</label>
                    <input type="file" @change="handleFileUpload" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls" />
                    <small class="p-error" v-if="importForm.errors.file">{{ importForm.errors.file }}</small>
                </div>
                
                <div class="flex justify-content-end mt-3">
                    <Button label="Proses Import" type="submit" :loading="importForm.processing" />
                </div>
            </form>
        </div>
    </Dialog>
    <Dialog v-model:visible="displayImportUpdateModal" header="Update Data Siswa" :modal="true" :style="{ width: '400px' }">
        <div class="flex flex-column gap-3">
            <p class="text-sm text-600">
                Pastikan NISN di Excel sesuai dengan data kelas di sistem. 
            </p>
            
            <a :href="route('admin.students.template-update')" class="no-underline">
                <Button label="Download Template Excel" icon="pi pi-download" severity="secondary" outlined class="w-full" />
            </a>

            <Divider />

            <form @submit.prevent="submitImportUpdate">
                <div class="field">
                    <label class="font-medium mb-2 block">Pilih File Excel (.xlsx)</label>
                    <input type="file" @change="handleFileUploadUpdate" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls" />
                    <small class="p-error" v-if="importUpdateForm.errors.file">{{ importUpdateForm.errors.file }}</small>
                </div>
                
                <div class="flex justify-content-end mt-3">
                    <Button label="Proses Import" type="submit" :loading="importUpdateForm.processing" />
                </div>
            </form>
        </div>
    </Dialog>
        <!-- DIALOG BULK EDIT NIS -->
        <Dialog v-model:visible="displayBulkEditModal" header="Bulk Edit NIS Massal (Via Excel)" :modal="true" :style="{ width: '600px' }">
            <div class="mb-4">
                <p class="text-600 m-0 line-height-3">
                    Langkah 1: Download data siswa sesuai filter saat ini (misal: hanya kelas X). <br>
                    Langkah 2: Edit NIS/NISN di Excel yang didownload.<br>
                    Langkah 3: Upload file Excel tersebut ke form di bawah ini.
                </p>
                
                <div class="mt-3">
                    <Button label="Download Data Filter Saat Ini" icon="pi pi-download" severity="info" class="w-full" @click="downloadBulkNisTemplate" />
                </div>
            </div>
            <hr class="mb-4">
            <form @submit.prevent="submitBulkEdit">
                <div class="field">
                    <label class="font-semibold block mb-2">Upload Excel Hasil Edit</label>
                    <input type="file" @change="handleFileUploadBulkNis" class="w-full p-2 border border-300 border-round" accept=".xlsx, .xls, .csv" required />
                    <small class="p-error" v-if="bulkEditForm.errors.file">{{ bulkEditForm.errors.file }}</small>
                </div>
                <div class="flex justify-content-end gap-2 mt-4">
                    <Button type="button" label="Batal" class="p-button-text" @click="displayBulkEditModal = false" />
                    <Button type="submit" label="Proses Upload & Update" class="p-button-primary" :loading="bulkEditForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import RadioButton from 'primevue/radiobutton';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Textarea from 'primevue/textarea';
import FloatingVue from 'floating-vue'; 
import 'floating-vue/dist/style.css';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    students: Object,
    classrooms: Array,
    religions: Array,
    filters: Object,
    activeYear: Object
});

const toast = useToast();
const confirm = useConfirm();

const displayModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);

// Filters State
const search = ref(props.filters?.search || '');
const selectedClass = ref(
    props.filters?.classroom_id 
        ? (Array.isArray(props.filters.classroom_id) 
            ? props.filters.classroom_id.map(Number) 
            : props.filters.classroom_id.split(',').map(Number)) 
        : []
);
const selectedReligion = ref(props.filters?.religion_id || null); // Ambil dari url jika ada
const selectedStatus = ref(props.filters?.status || 'aktif');
const selectedPerPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);

const statusOptions = ref([
    { label: 'Siswa Aktif', value: 'aktif' },
    { label: 'Siswa Alumni (Lulus)', value: 'lulus' }
]);
const perPageOptions = ref([
    { label: '10 Data', value: 10 },
    { label: '50 Data', value: 50 },
    { label: '100 Data', value: 100 }
]);


// --- SEARCH & FILTER LOGIC ---
const handleFilterChange = () => {
    applyFilters();
};


let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const applyFilters = () => {
    const classIds = selectedClass.value && selectedClass.value.length > 0 ? selectedClass.value.join(',') : null;
    router.get(route('admin.students.index'), {
        search: search.value,
        classroom_id: classIds,
        religion_id: selectedReligion.value,
        status: selectedStatus.value,
        trash: props.filters?.trash,
        per_page: selectedPerPage.value
    }, { preserveState: true, replace: true });
};

const exportExcel = () => {
    const classIds = selectedClass.value && selectedClass.value.length > 0 ? selectedClass.value.join(',') : '';
    const params = new URLSearchParams({
        search: search.value || '',
        classroom_id: classIds,
        religion_id: selectedReligion.value || '',
        status: selectedStatus.value || '',
        trash: props.filters?.trash || ''
    }).toString();
    
    window.open(route('admin.students.export') + '?' + params, '_blank');
};

const toggleTrash = () => {
    const isTrash = props.filters?.trash === 'true';
    const classIds = selectedClass.value && selectedClass.value.length > 0 ? selectedClass.value.join(',') : null;
    router.get(route('admin.students.index'), {
        trash: !isTrash ? 'true' : null,
        classroom_id: classIds, 
        religion_id: selectedReligion.value,
        status: selectedStatus.value,
        search: search.value
    }, { preserveState: true });
};

// --- FORM LOGIC ---
const form = useForm({
    full_name: '',
    email: '',
    classroom_id: '',
    nis: '',
    nisn: '',
    gender: true,
    phone: '',
    religion_id: '',
    birth_place: '',
    birth_date: '',
    address: '',
    
    nik: '',
    no_kk: '',
    akta_no: '',
    citizenship: 'WNI',
    special_needs: '',
    rt: '',
    rw: '',
    dusun: '',
    kelurahan: '',
    kecamatan: '',
    postal_code: '',
    latitude: '',
    longitude: '',
    residence_type: '',
    transportation: '',
    child_order: 1,
    
    father_name: '',
    father_deceased: false,
    father_nik: '',
    father_birth_year: '',
    father_education: '',
    father_job: '',
    father_income: '',
    father_special_needs: '',
    father_phone: '',
    
    mother_name: '',
    mother_deceased: false,
    mother_nik: '',
    mother_birth_year: '',
    mother_education: '',
    mother_job: '',
    mother_income: '',
    mother_special_needs: '',
    mother_phone: '',
    
    guardian_name: '',
    guardian_nik: '',
    guardian_birth_year: '',
    guardian_education: '',
    guardian_job: '',
    guardian_income: '',
    guardian_phone: '',
    has_guardian: false,
    
    height: '',
    weight: '',
    head_circumference: '',
    distance_to_school_km: '',
    travel_time_minutes: '',
    sibling_count: '',
    periodik_phone: '',
    
    prev_school_type: '',
    prev_school_status: '',
    prev_school_name: '',
});
const handleSuccess = (message) => {
    displayModal.value = false;
    form.reset();
};

const handleError = (errors) => {
    let msg = 'Terjadi kesalahan.';

    if (errors?.email) msg = errors.email;
    if (errors?.nip) msg = errors.nip;

    toast.add({
        severity: 'error',
        summary: 'Gagal',
        detail: msg,
        life: 3000
    });
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    // Auto-select class jika filter sedang aktif (ambil kelas pertama jika ada)
    if (selectedClass.value && selectedClass.value.length === 1) {
        form.classroom_id = selectedClass.value[0];
    } else {
        form.classroom_id = null;
    }
    displayModal.value = true;
};

const confirmGenerateNis = () => {
    let startNis = window.prompt("Masukkan NIS awal (opsional, kosongkan untuk otomatis melanjutkan dari NIS tertinggi termasuk siswa yang sudah keluar):", "");
    
    if (startNis !== null) {
        // Hapus karakter non-digit
        startNis = startNis.replace(/\D/g, '');
        
        router.post(route('admin.students.generate-nis'), { start_nis: startNis ? parseInt(startNis) : null }, {
            preserveScroll: true
        });
    }
};

const openEditModal = (data) => {
    isEditing.value = true;
    editId.value = data.id;
    
    // Mapping Data
    form.full_name = data.full_name;
    form.email = data.user?.email;
    form.classroom_id = (data.classrooms && data.classrooms.length > 0) ? data.classrooms[0].id : null;
    form.nis = data.nis;
    form.nisn = data.nisn;
    form.gender = data.gender;
    form.phone = data.phone;
    form.religion_id = data.religion_id;
    form.birth_place = data.birth_place;
    form.birth_date = data.birth_date ? data.birth_date.split('T')[0] : '';
    form.address = data.address;
    
    form.nik = data.nik || '';
    form.no_kk = data.no_kk || '';
    form.akta_no = data.akta_no || '';
    form.citizenship = data.citizenship || 'WNI';
    form.special_needs = data.special_needs || '';
    form.rt = data.rt || '';
    form.rw = data.rw || '';
    form.dusun = data.dusun || '';
    form.kelurahan = data.kelurahan || '';
    form.kecamatan = data.kecamatan || '';
    form.postal_code = data.postal_code || '';
    form.latitude = data.latitude || '';
    form.longitude = data.longitude || '';
    form.residence_type = data.residence_type || '';
    form.transportation = data.transportation || '';
    form.child_order = data.child_order || 1;
    
    form.father_name = data.father_name || '';
    form.father_deceased = data.father_deceased === 1 || data.father_deceased === true || false;
    form.father_nik = data.father_nik || '';
    form.father_birth_year = data.father_birth_year || '';
    form.father_education = data.father_education || '';
    form.father_job = data.father_job || '';
    form.father_income = data.father_income || '';
    form.father_special_needs = data.father_special_needs || '';
    form.father_phone = data.father_phone || '';
    
    form.mother_name = data.mother_name || '';
    form.mother_deceased = data.mother_deceased === 1 || data.mother_deceased === true || false;
    form.mother_nik = data.mother_nik || '';
    form.mother_birth_year = data.mother_birth_year || '';
    form.mother_education = data.mother_education || '';
    form.mother_job = data.mother_job || '';
    form.mother_income = data.mother_income || '';
    form.mother_special_needs = data.mother_special_needs || '';
    form.mother_phone = data.mother_phone || '';
    
    form.guardian_name = data.guardian_name || '';
    form.guardian_nik = data.guardian_nik || '';
    form.guardian_birth_year = data.guardian_birth_year || '';
    form.guardian_education = data.guardian_education || '';
    form.guardian_job = data.guardian_job || '';
    form.guardian_income = data.guardian_income || '';
    form.guardian_phone = data.guardian_phone || '';
    form.has_guardian = data.guardian_name ? true : false;
    
    form.height = data.height || '';
    form.weight = data.weight || '';
    form.head_circumference = data.head_circumference || '';
    form.distance_to_school_km = data.distance_to_school_km || '';
    form.travel_time_minutes = data.travel_time_minutes || '';
    form.sibling_count = data.sibling_count || '';
    
    form.prev_school_type = data.prev_school_type || '';
    form.prev_school_status = data.prev_school_status || '';
    form.prev_school_name = data.prev_school_name || '';

    displayModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.students.update', editId.value), {
            onSuccess: () => { displayModal.value = false; handleSuccess(); form.reset(); },
        });
    } else {
        form.post(route('admin.students.store'), {
            onSuccess: () => { displayModal.value = false; handleSuccess(); form.reset(); }
        });
    }
};

const confirmDelete = (data) => {
    confirm.require({
        message: `Non-aktifkan siswa <b>${data.full_name}</b>?`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.students.destroy', data.id), { onSuccess: () => handleSuccess() })
    });
};

const confirmRestore = (data) => {
    confirm.require({
        message: `Pulihkan siswa <b>${data.full_name}</b>?`,
        header: 'Restore',
        icon: 'pi pi-refresh',
        acceptClass: 'p-button-success',
        accept: () => router.put(route('admin.students.restore', data.id), {}, { onSuccess: () => handleSuccess() })
    });
};

const confirmForceDelete = (data) => {
    confirm.require({
        message: `Hapus PERMANEN <b>${data.full_name}</b>? Data nilai dll akan hilang.`,
        header: 'Bahaya',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.students.force-delete', data.id), { onSuccess: () => handleSuccess() })
    });
};
// State
const displayImportModal = ref(false);
const importForm = useForm({ file: null });
const displayImportUpdateModal = ref(false);
const importUpdateForm = useForm({ file: null });

// --- IMPORT LOGIC --- Data Utama

// Functions
const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    displayImportModal.value = true;
};

const handleFileUpload = (event) => {
    importForm.file = event.target.files[0];
};

const submitImport = () => {
    if (!importForm.file) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Pilih file terlebih dahulu', life: 3000 });
        return;
    }
    importForm.post(route('admin.students.import'), {
        onSuccess: () => {
            
            displayImportModal.value = false;
            handleSuccess();
            importForm.reset();
        },
        onError: () => {
            handleError('Terjadi kesalahan saat import: ' + importForm.errors);
        }
    });
};
// Update Data Kosong
const openImportUpdateModal = () => {
    importUpdateForm.reset();
    importUpdateForm.clearErrors();
    displayImportUpdateModal.value = true;
};
const handleFileUploadUpdate = (event) => {
    importUpdateForm.file = event.target.files[0];
};
const submitImportUpdate = () => {
    if (!importUpdateForm.file) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Pilih file terlebih dahulu', life: 3000 });
        return;
    }
    importUpdateForm.post(route('admin.students.import-update'), {
        onSuccess: () => {
            displayImportUpdateModal.value = false;
            handleSuccess();
            importUpdateForm.reset();
        },
        onError: () => {
            handleError('Terjadi kesalahan saat import: ' + importUpdateForm.errors);
        }
    });
};
const selectedStudents = ref([]);
const displayBulkEditModal = ref(false);
const bulkEditForm = useForm({
    file: null
});

const openBulkEditModal = () => {
    displayBulkEditModal.value = true;
};

const downloadBulkNisTemplate = () => {
    const classIds = selectedClass.value && selectedClass.value.length > 0 ? selectedClass.value.join(',') : '';
    const params = new URLSearchParams({
        search: search.value || '',
        classroom_id: classIds,
        religion_id: selectedReligion.value || '',
        status: selectedStatus.value || '',
        trash: props.filters?.trash || ''
    }).toString();
    
    window.open(route('admin.students.export-bulk-nis') + '?' + params, '_blank');
};

const handleFileUploadBulkNis = (e) => {
    bulkEditForm.file = e.target.files[0];
};

const submitBulkEdit = () => {
    bulkEditForm.post(route('admin.students.import-bulk-nis'), {
        preserveScroll: true,
        onSuccess: () => {
            displayBulkEditModal.value = false;
            bulkEditForm.reset();
            handleSuccess();
        },
        onError: () => {
            handleError('Terjadi kesalahan saat menyimpan perubahan NIS.');
        }
    });
};
</script>
<style scoped>
.datatable-container {
    overflow-x: auto;
    max-width: 100%;
}

/* Header tabel lebih kuat */
.p-datatable .p-datatable-thead > tr > th {
    background: #f8fafc;
    font-weight: 600;
    white-space: nowrap;
}

/* Freeze column styling */
.p-datatable .p-datatable-frozen-column {
    background: white;
}

/* Row hover */
.p-datatable tbody tr:hover {
    background: #f1f5f9;
}

/* Scrollbar lebih bagus */
.datatable-container::-webkit-scrollbar {
    height: 8px;
}

.datatable-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.datatable-scroll {
    overflow-x: auto;
    width: 100%;
}
</style>