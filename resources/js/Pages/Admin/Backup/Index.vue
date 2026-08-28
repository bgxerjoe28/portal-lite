<template>
    <AppLayout title="Manajemen Backup">
        <div class="p-4">
            <div class="flex flex-column md:flex-row justify-content-between align-items-center gap-3 mb-5">
                <div>
                    <div class="flex align-items-center gap-2">
                        <h2 class="text-3xl font-bold m-0 text-900">Pusat Cadangan Data</h2>
                        <span v-if="is_production" class="bg-red-100 text-red-800 text-xs px-2 py-1 border-round-md font-bold border-1 border-red-300 flex align-items-center gap-1">
                            <i class="pi pi-shield"></i> SERVER PRODUKSI
                        </span>
                        <span v-else class="bg-blue-100 text-blue-800 text-xs px-2 py-1 border-round-md font-semibold border-1 border-blue-200 flex align-items-center gap-1">
                            <i class="pi pi-info-circle"></i> {{ (app_env || 'STAGING / DEV').toUpperCase() }}
                        </span>
                    </div>
                    <p class="text-500 m-0 mt-1">Kelola dan amankan database serta file berkas portal SMAN 16</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button 
                        label="Cek MinIO / S3" 
                        icon="pi pi-cloud" 
                        severity="info" 
                        outlined 
                        @click="checkS3" 
                        :loading="checkingS3"
                    />
                    <Button 
                        label="Restore via Upload" 
                        icon="pi pi-upload" 
                        severity="help" 
                        outlined 
                        @click="displayUploadModal = true" 
                    />
                    <Button 
                        label="Backup Lokal (Tanpa S3)" 
                        icon="pi pi-desktop" 
                        severity="primary" 
                        @click="runBackup(true)" 
                        :loading="loading"
                        outlined
                    />
                    <Button 
                        label="Mulai Backup Full (+S3)" 
                        icon="pi pi-database" 
                        severity="success" 
                        @click="runBackup(false)" 
                        :loading="loading"
                        raised
                    />
                </div>
            </div>

            <div class="grid mb-4">
                <div class="col-12 md:col-6 lg:col-3">
                    <div class="surface-card p-4 shadow-1 border-round-xl border-left-4 border-orange-500">
                        <div class="flex justify-content-between mb-3">
                            <div>
                                <span class="block text-500 font-medium mb-2">Sisa Kapasitas Server</span>
                                <div class="text-900 font-bold text-xl">{{ disk_usage.free }}</div>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-orange-100 border-round" style="width:2.5rem;height:2.5rem">
                                <i class="pi pi-hdd text-orange-500 text-xl"></i>
                            </div>
                        </div>
                        <span class="text-500 text-sm">Total: {{ disk_usage.total }}</span>
                    </div>
                </div>
                <div class="col-12 md:col-6 lg:col-3">
                    <div class="surface-card p-4 shadow-1 border-round-xl border-left-4 border-blue-500">
                        <div class="flex justify-content-between mb-3">
                            <div>
                                <span class="block text-500 font-medium mb-2">Total File Backup</span>
                                <div class="text-900 font-bold text-xl">{{ backups.length }} File</div>
                            </div>
                            <div class="flex align-items-center justify-content-center bg-blue-100 border-round" style="width:2.5rem;height:2.5rem">
                                <i class="pi pi-file-zip text-blue-500 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-6 lg:col-3">
                    <div class="surface-card p-4 shadow-1 border-round-xl mb-4 border-left-4" 
                        :class="system_check.is_ready ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'">
                        
                        <div class="flex align-items-center gap-3">
                            <i :class="system_check.is_ready ? 'pi pi-check-circle text-green-600' : 'pi pi-exclamation-triangle text-red-600'" 
                            class="text-3xl"></i>
                            <div>
                                <h4 class="m-0 font-bold" :class="system_check.is_ready ? 'text-green-800' : 'text-red-800'">
                                    Status Kesiapan Sistem: {{ system_check.is_ready ? 'Siap' : 'Bermasalah' }}
                                </h4>
                                <div class="flex flex-wrap gap-3 mt-2">
                                    <div v-for="(item, key) in system_check.details" :key="key" class="flex align-items-center gap-1">
                                        <i :class="item.installed ? 'pi pi-check text-green-500' : 'pi pi-times text-red-500'" class="text-xs"></i>
                                        <small class="text-700">{{ item.label }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </div>

            <!-- Card Diagnostik Uvicorn Python IRT Microservice -->
            <div class="surface-card p-4 shadow-1 border-round-xl mb-4 border-left-4"
                :class="uvicorn_status?.is_online ? 'border-purple-500' : 'border-red-500'">
                <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-3">
                    <div class="flex align-items-center gap-3">
                        <div class="flex align-items-center justify-content-center border-round-lg p-3"
                            :class="uvicorn_status?.is_online ? 'bg-purple-100 text-purple-600' : 'bg-red-100 text-red-600'">
                            <i class="pi pi-cog text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap align-items-center gap-2">
                                <h3 class="m-0 font-bold text-900 text-lg">Diagnostik Uvicorn Python IRT Microservice</h3>
                                <span v-if="uvicorn_status?.is_online" class="bg-purple-100 text-purple-700 text-xs px-2 py-1 border-round-md font-bold">
                                    <i class="pi pi-check-circle mr-1"></i>Service Online ({{ uvicorn_status?.response_time_ms }} ms)
                                </span>
                                <span v-else class="bg-red-100 text-red-700 text-xs px-2 py-1 border-round-md font-bold">
                                    <i class="pi pi-times-circle mr-1"></i>Service Offline
                                </span>
                            </div>
                            <p class="text-500 text-xs mt-1 mb-0">
                                Memeriksa URL server Uvicorn <code>.env</code> ({{ uvicorn_status?.url }}) & Token Keamanan Internal (X-CBT-Secret).
                                <span v-if="uvicorn_status?.last_checked_at" class="text-400 font-semibold ml-2">Diperbarui: {{ uvicorn_status?.last_checked_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid text-sm">
                    <div class="col-12 md:col-6">
                        <div class="p-3 surface-100 border-round">
                            <div class="text-500 font-medium mb-1">Status Koneksi API</div>
                            <div class="font-bold" :class="uvicorn_status?.is_online ? 'text-green-600' : 'text-red-600'">
                                <i :class="uvicorn_status?.is_online ? 'pi pi-check-circle' : 'pi pi-times-circle'" class="mr-1"></i>
                                {{ uvicorn_status?.is_online ? 'Berhasil Terhubung (200 OK)' : 'Gagal Terhubung' }}
                            </div>
                            <small v-if="uvicorn_status?.error_message" class="text-red-500 block mt-1 font-mono text-xs">
                                {{ uvicorn_status?.error_message }}
                            </small>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="p-3 surface-100 border-round">
                            <div class="text-500 font-medium mb-1">Token Keamanan Header (X-CBT-Secret)</div>
                            <div class="font-bold flex align-items-center gap-2" :class="uvicorn_status?.secret_configured ? 'text-green-600' : 'text-orange-600'">
                                <i :class="uvicorn_status?.secret_configured ? 'pi pi-shield' : 'pi pi-exclamation-triangle'"></i>
                                {{ uvicorn_status?.secret_configured ? 'Terproteksi: ' + uvicorn_status?.secret_masked : 'Belum Diatur di .env' }}
                            </div>
                            <small class="text-500 block mt-1 text-xs">
                                Memastikan API hanya dapat diakses oleh server Portal / Staging yang sah.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Diagnostik Storage MinIO / S3 -->
            <div class="surface-card p-4 shadow-1 border-round-xl mb-4 border-left-4"
                :class="s3Data?.is_s3_active ? 'border-green-500' : (s3Data?.all_config_ok ? 'border-yellow-500' : 'border-400')">
                <div class="flex flex-column md:flex-row justify-content-between md:align-items-center gap-3 mb-3">
                    <div class="flex align-items-center gap-3">
                        <div class="flex align-items-center justify-content-center border-round-lg p-3"
                            :class="s3Data?.is_s3_active ? 'bg-green-100 text-green-600' : (s3Data?.all_config_ok ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-600')">
                            <i class="pi pi-cloud text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap align-items-center gap-2">
                                <h3 class="m-0 font-bold text-900 text-lg">Diagnostik Storage Cloud (MinIO / S3)</h3>
                                <span v-if="s3Data?.is_s3_active" class="bg-green-100 text-green-700 text-xs px-2 py-1 border-round-md font-bold">
                                    <i class="pi pi-check-circle mr-1"></i>Koneksi Aktif ({{ s3Data?.storage_test?.response_time_ms }} ms)
                                </span>
                                <span v-else-if="s3Data?.all_config_ok" class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 border-round-md font-bold">
                                    <i class="pi pi-exclamation-triangle mr-1"></i>Kredensial Diisi (Koneksi Terkendala)
                                </span>
                                <span v-else class="bg-gray-100 text-gray-700 text-xs px-2 py-1 border-round-md font-bold">
                                    <i class="pi pi-info-circle mr-1"></i>Mode Storage Lokal
                                </span>
                            </div>
                            <p class="text-500 text-xs mt-1 mb-0">
                                Memeriksa variabel `.env` dan menguji operasi Write, Read, & Delete secara real-time ke MinIO / S3.
                                <span v-if="s3Data?.last_checked_at" class="text-400 font-semibold ml-2">Diperbarui: {{ s3Data?.last_checked_at }}</span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <Button 
                            label="Uji Ulang Koneksi MinIO" 
                            icon="pi pi-refresh" 
                            severity="secondary" 
                            size="small"
                            outlined 
                            @click="checkS3" 
                            :loading="checkingS3"
                        />
                    </div>
                </div>

                <div v-if="s3Data">
                    <div v-for="(data, key) in s3Data" :key="key" class="mb-5 border-1 border-300 border-round p-3 surface-50">
                        <h4 class="mt-0 mb-3 font-bold text-primary"><i class="pi pi-server mr-2"></i>{{ key === 'cbt' ? 'Koneksi S3 CBT (IDCloudHost/Public)' : 'Koneksi S3 Local (MinIO/Internal)' }}</h4>
                        
                        <!-- Alert / Status Hasil Operasi Read/Write -->
                        <div v-if="data?.storage_test" class="p-3 border-round-lg mb-4 text-sm"
                            :class="data.storage_test.success ? 'bg-green-50 text-green-900 border-1 border-green-200' : 'bg-red-50 text-red-900 border-1 border-red-200'">
                            <div class="flex align-items-start gap-2">
                                <i :class="data.storage_test.success ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-600'" class="text-lg mt-1"></i>
                                <div class="flex-1">
                                    <strong>{{ data.storage_test.success ? 'Status Tes Storage: OK' : 'Status Tes Storage: Gagal' }}</strong>
                                    <p class="m-0 mt-1 text-xs">{{ data.storage_test.message }}</p>
                                    <div v-if="data.storage_test.error_details" class="mt-2 p-2 bg-white border-round text-xs font-mono text-red-800 surface-border border-1 overflow-x-auto">
                                        {{ data.storage_test.error_details }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grid Pengecekan Setting .env -->
                        <div class="grid text-xs">
                            <div v-for="(item, itemKey) in data?.config_checks" :key="itemKey" class="col-12 md:col-6 lg:col-4 xl:col-2">
                                <div class="p-3 border-round-lg surface-100 flex flex-column gap-1 h-full shadow-1">
                                    <div class="flex justify-content-between align-items-center mb-1">
                                        <span class="font-bold text-700">{{ item.label }}</span>
                                        <i :class="item.status ? 'pi pi-check-circle text-green-500' : 'pi pi-exclamation-circle text-yellow-500'"></i>
                                    </div>
                                    <div class="font-mono text-xs p-1 surface-0 border-round border-1 border-300 text-900 text-overflow-ellipsis overflow-hidden">
                                        {{ item.key }}={{ item.value }}
                                    </div>
                                    <small class="text-500 text-xs mt-1" :class="item.status ? 'text-green-700' : 'text-orange-700'">
                                        {{ item.message }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="surface-card p-4 shadow-1 border-round-xl mb-4">
                <div class="flex align-items-center gap-2 mb-4">
                    <i class="pi pi-info-circle text-primary text-xl"></i>
                    <h3 class="m-0 text-900 font-bold text-lg">Informasi Sistem</h3>
                </div>
                <div class="grid">
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#6366f1,#8b5cf6)">
                                    <i class="pi pi-desktop text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">Sistem Operasi</span>
                            </div>
                            <span class="text-900 font-semibold text-sm">{{ system_info.os }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#0ea5e9,#06b6d4)">
                                    <i class="pi pi-database text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">Database</span>
                            </div>
                            <span class="text-900 font-semibold text-sm">{{ system_info.db }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#f59e0b,#f97316)">
                                    <i class="pi pi-code text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">PHP</span>
                            </div>
                            <span class="text-900 font-semibold text-sm">PHP {{ system_info.php }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#ef4444,#f43f5e)">
                                    <i class="pi pi-cog text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">Laravel</span>
                            </div>
                            <span class="text-900 font-semibold text-sm">Laravel {{ system_info.laravel }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#10b981,#059669)">
                                    <i class="pi pi-bookmark text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">Versi Aplikasi</span>
                            </div>
                            <span class="text-900 font-semibold text-sm">v{{ system_info.app }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6 lg:col-4 xl:col-2">
                        <div class="flex flex-column gap-1 p-3 border-round-lg surface-100 h-full">
                            <div class="flex align-items-center gap-2 mb-1">
                                <span class="flex align-items-center justify-content-center border-round" style="width:2rem;height:2rem;background:linear-gradient(135deg,#8b5cf6,#d946ef)">
                                    <i class="pi pi-bolt text-white text-sm"></i>
                                </span>
                                <span class="text-500 text-xs font-medium uppercase tracking-widest">Octane Server</span>
                            </div>
                            <div class="flex align-items-center justify-content-between mt-auto pt-2">
                                <span class="font-bold text-sm" :class="octaneStatus.is_octane ? 'text-indigo-600' : 'text-600'">
                                    {{ octaneStatus.is_octane ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                                <a href="/pulse" target="_blank" v-if="octaneStatus.is_octane" v-tooltip.top="'Buka Dasbor Pulse'" class="text-indigo-500 hover:text-indigo-700 transition-colors">
                                    <i class="pi pi-chart-line text-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="surface-card p-4 shadow-1 border-round-xl">
                <DataTable v-bind="$pagination({ label: 'backups' })" :value="backups" stripedRows class="p-datatable-sm">
                    <template #empty>
                        <div class="text-center p-4 text-500">Belum ada file backup yang tersimpan.</div>
                    </template>
                    
                    <Column header="No." style="width: 50px">
                        <template #body="{ index }">{{ index + 1 }}</template>
                    </Column>
                    
                    <Column field="file_name" header="Nama File Backup" sortable>
                        <template #body="{ data }">
                            <span class="font-mono text-sm text-700">{{ data.file_name }}</span>
                        </template>
                    </Column>
                    
                    <Column field="file_size" header="Ukuran" sortable class="text-center" style="width: 120px" />
                    
                    <Column field="last_modified" header="Tanggal Dibuat" sortable style="width: 250px" />
                    
                    <Column header="Aksi" class="text-center" style="width: 150px">
                        <template #body="{ data }">
                            <div class="flex justify-content-center gap-2">
                                <Button 
                                  
                                    icon="pi pi-refresh" 
                                    severity="warning" 
                                    text 
                                    rounded 
                                    @click="confirmRestore(data.file_name)"
                                    v-tooltip.top="'Restore Database & Berkas'"
                                />
                                <Button 
                                    icon="pi pi-key" 
                                    severity="danger" 
                                    text 
                                    rounded 
                                    @click="openForceRestoreModal({ type: 'file', fileName: data.file_name })"
                                    v-tooltip.top="'Force Restore (Beda Versi Database)'"
                                />
                                <Button 
                                    icon="pi pi-cloud-upload" 
                                    severity="info" 
                                    text 
                                    rounded 
                                    @click="syncToS3(data.file_name)"
                                    v-tooltip.top="'Kirim ke S3 Minio'"
                                />
                                <Button 
                                    icon="pi pi-download" 
                                    severity="success" 
                                    text 
                                    rounded 
                                    @click="downloadFile(data.file_name)"
                                    v-tooltip.top="'Download'"
                                />
                                <Button 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    text 
                                    rounded 
                                    @click="confirmDelete(data.file_name)"
                                    v-tooltip.top="'Hapus'"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        <Dialog 
            v-model:visible="displayUploadModal" 
            header="Upload File Backup" 
            :modal="true" 
            class="w-full md:w-5"
        >
            <div class="flex flex-column gap-3">
                <p class="text-sm text-600">
                    <i class="pi pi-exclamation-triangle text-warning"></i> 
                    <b>Peringatan:</b> Proses ini akan menghapus database dan berkas fisik saat ini, lalu menggantinya dengan data dari file backup.
                </p>
                
                <form @submit.prevent="submitUploadRestore">
                    <div class="field">
                        <label class="font-bold mb-2 block">Pilih File ZIP Backup</label>
                        <input 
                            type="file" 
                            @change="onFileSelect" 
                            accept=".zip" 
                            class="w-full p-3 border surface-border border-round surface-100"
                        />
                    </div>

                    <div class="flex justify-content-end gap-2 mt-4">
                        <Button label="Batal" severity="secondary" text @click="displayUploadModal = false" />
                        <Button label="Mulai Restore" icon="pi pi-upload" severity="danger" type="submit" :loading="uploadForm.processing" />
                    </div>
                    
                    <div class="field mt-4 pt-3 border-top-1 surface-border">
                        <div class="flex align-items-center gap-2">
                            <Checkbox v-model="forceUploadRestore" :binary="true" inputId="forceUpload" />
                            <label for="forceUpload" class="text-sm text-700 cursor-pointer font-semibold">
                                Force Restore (Abaikan perbedaan versi database)
                            </label>
                        </div>
                        <div v-if="forceUploadRestore" class="mt-2 p-2 bg-red-50 border-round border-1 border-red-200">
                            <label class="block text-xs font-bold text-red-700 mb-1">Keyword Challenge (Wajib: FORCE_RESTORE)</label>
                            <InputText v-model="uploadForm.challenge_keyword" placeholder="Ketik: FORCE_RESTORE" class="w-full text-sm font-mono uppercase" />
                        </div>
                    </div>
                </form>
            </div>
        </Dialog>

        <!-- Modal Progres (Backup & Restore) -->
        <Dialog 
            v-model:visible="displayProgressModal" 
            :header="progressTitle" 
            :modal="true" 
            :closable="false"
            :draggable="false"
            class="w-full md:w-5"
        >
            <div class="flex flex-column align-items-center p-4 text-center">
                <div class="relative mb-4 flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                    <div class="absolute w-full h-full border-4 surface-border border-round-circle"></div>
                    <div 
                        class="absolute w-full h-full border-4 border-round-circle spinner-rotate"
                        :class="progressMode === 'restore' ? 'border-warning' : 'border-primary'"
                    ></div>
                    <i 
                        :class="progressMode === 'restore' ? 'pi pi-refresh text-warning' : 'pi pi-database text-primary'"
                        class="text-3xl"
                    ></i>
                </div>
                
                <h3 class="text-900 font-bold mb-2">{{ progressTitle }}</h3>
                <p class="text-600 mb-4 text-sm">{{ progressMessage }}</p>
                
                <!-- Progress Bar -->
                <div class="w-full border-round-md overflow-hidden mb-3" style="height: 12px; background-color: #f1f5f9;">
                    <div 
                        class="h-full transition-all transition-duration-500 border-round-md" 
                        :style="{ 
                            width: progressValue + '%', 
                            background: progressMode === 'restore' 
                                ? 'linear-gradient(90deg, #f59e0b 0%, #ef4444 100%)'
                                : 'linear-gradient(90deg, var(--primary-color) 0%, #3b82f6 100%)'
                        }"
                    ></div>
                </div>
                
                <span 
                    class="font-bold text-lg"
                    :class="progressMode === 'restore' ? 'text-warning' : 'text-primary'"
                >{{ progressValue }}%</span>

                <p v-if="progressMode === 'restore'" class="text-xs text-500 mt-3">
                    <i class="pi pi-info-circle mr-1"></i>
                    Jangan tutup halaman ini. Aplikasi sedang dalam mode maintenance.
                </p>
            </div>
        </Dialog>

        <!-- Dialog Force Restore Challenge Keyword -->
        <Dialog 
            v-model:visible="displayForceRestoreModal" 
            modal 
            header="Konfirmasi Force Restore Database" 
            :style="{ width: '480px' }"
            class="p-fluid"
        >
            <div class="flex flex-column gap-3">
                <div class="flex align-items-center gap-3 p-3 bg-red-50 border-round border-1 border-red-300">
                    <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
                    <div class="text-sm text-red-700">
                        <strong>Peringatan Kritikal:</strong> File backup ini memiliki versi migrasi database yang berbeda dari server saat ini (atau format lama tanpa manifest).
                    </div>
                </div>

                <p class="text-600 text-sm m-0">
                    Untuk memaksakan restore dan mengabaikan perbedaan versi database, ketik keyword challenge 
                    <code class="font-bold text-red-600 bg-red-100 px-2 py-1 border-round">FORCE_RESTORE</code> di bawah ini:
                </p>

                <div class="field">
                    <label class="font-bold text-700">Keyword Challenge (Wajib: FORCE_RESTORE):</label>
                    <InputText 
                        v-model="forceChallengeKeyword" 
                        placeholder="Ketik: FORCE_RESTORE" 
                        class="w-full font-mono text-center text-lg uppercase"
                        @keyup.enter="executeForceRestore"
                    />
                    <small class="text-red-500 font-medium" v-if="forceChallengeError">{{ forceChallengeError }}</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-content-end gap-2">
                    <Button 
                        label="Batal" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text 
                        @click="closeForceRestoreModal" 
                    />
                    <Button 
                        label="Jalankan Force Restore" 
                        icon="pi pi-exclamation-circle" 
                        severity="danger" 
                        :loading="loading"
                        @click="executeForceRestore" 
                    />
                </div>
            </template>
        </Dialog>

        <!-- Dialog Peringatan Bahaya Khusus Server Produksi -->
        <Dialog 
            v-model:visible="displayProdRestoreModal" 
            modal 
            header="⚠️ PERINGATAN BAHAYA KRITIS: RESTORE DI SERVER PRODUKSI" 
            :style="{ width: '580px' }"
            class="p-fluid"
        >
            <div class="flex flex-column gap-3">
                <div class="p-3 bg-red-100 border-round border-1 border-red-400 text-red-900">
                    <div class="flex align-items-center gap-2 mb-2 font-bold text-base">
                        <i class="pi pi-exclamation-triangle text-xl text-red-600"></i>
                        <span>Anda Berada di Lingkungan PRODUKSI (Live)</span>
                    </div>
                    <p class="m-0 text-sm leading-normal">
                        Proses restore bersifat <strong>destruktif</strong> dan akan mengosongkan seluruh database yang aktif saat ini.
                    </p>
                </div>

                <div class="surface-100 p-3 border-round border-1 surface-border">
                    <div class="font-bold text-sm text-900 mb-2 flex align-items-center gap-2">
                        <i class="pi pi-shield text-red-600"></i>
                        <span>Rincian Risiko & Dampak Bahaya:</span>
                    </div>
                    <ul class="m-0 pl-3 text-xs text-700 flex flex-column gap-2 line-height-3">
                        <li>
                            <strong class="text-red-700">1. Database Wipe:</strong> Database aktif <code>{{ db_name || 'portal_db' }}</code> akan dikosongkan total (<code>db:wipe</code>) sebelum data backup dimasukkan.
                        </li>
                        <li>
                            <strong class="text-red-700">2. Kehilangan Data Terkini:</strong> Data absensi, nilai rapor, jawaban ujian CBT, dan transaksi yang tercatat <u>setelah tanggal pembuatan file backup</u> akan <strong>hilang permanen</strong>.
                        </li>
                        <li>
                            <strong class="text-red-700">3. Penimpaan Berkas Storage:</strong> Foto/dokumen unggahan di <code>storage/app/public</code> dan <code>public/uploads</code> akan ditimpa data arsip lama.
                        </li>
                        <li>
                            <strong class="text-red-700">4. Downtime Sistem:</strong> Portal otomatis masuk mode pemeliharaan (<em>maintenance</em>) selama proses berjalan.
                        </li>
                    </ul>
                </div>

                <div class="field mt-1 mb-0">
                    <label class="font-bold text-sm text-900 block mb-1">
                        Ketik persis nama database <code class="bg-red-50 text-red-700 px-2 py-1 border-round border-1 border-red-200">{{ db_name || 'portal_db' }}</code> untuk mengonfirmasi:
                    </label>
                    <InputText 
                        v-model="prodConfirmInput" 
                        :placeholder="'Ketik persis: ' + (db_name || 'portal_db')" 
                        class="w-full font-mono text-base"
                        @keyup.enter="executeProdRestore"
                    />
                    <small class="text-red-600 font-semibold block mt-1" v-if="prodConfirmError">{{ prodConfirmError }}</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-content-end gap-2">
                    <Button 
                        label="Batal" 
                        icon="pi pi-times" 
                        severity="secondary" 
                        text 
                        @click="closeProdRestoreModal" 
                    />
                    <Button 
                        label="Saya Paham Risikonya & Mulai Restore" 
                        icon="pi pi-exclamation-triangle" 
                        severity="danger" 
                        :loading="loading"
                        :disabled="!prodConfirmInput || (prodConfirmInput.trim().toLowerCase() !== (db_name || '').trim().toLowerCase() && prodConfirmInput.trim().toUpperCase() !== 'RESTORE_PRODUKSI')"
                        @click="executeProdRestore" 
                    />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from "primevue/usetoast";
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    backups: Array,
    system_check: Object,
    disk_usage: Object,
    system_info: Object,
    s3_status: Object,
    uvicorn_status: Object,
    is_production: Boolean,
    app_env: String,
    db_name: String,
});

const loading = ref(false);
const confirm = useConfirm();
const toast = useToast();
const displayUploadModal = ref(false);
const s3Data = ref(props.s3_status || null);
const checkingS3 = ref(false);

const checkS3 = () => {
    checkingS3.value = true;
    axios.get(route('admin.backups.check-s3'))
        .then(res => {
            s3Data.value = res.data;
            let successCount = 0;
            if (res.data.cbt?.is_s3_active) successCount++;
            if (res.data.local?.is_s3_active) successCount++;

            if (successCount === 2) {
                toast.add({
                    severity: 'success',
                    summary: 'S3 Aktif (CBT & Local)',
                    detail: 'Koneksi ke kedua server S3 berhasil.',
                    life: 5000
                });
            } else if (successCount === 1) {
                toast.add({
                    severity: 'warn',
                    summary: 'S3 Parsial',
                    detail: 'Hanya salah satu server S3 yang berhasil terhubung.',
                    life: 6000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'S3 Terkendala',
                    detail: 'Koneksi ke kedua server S3 gagal.',
                    life: 6000
                });
            }
        })
        .catch(err => {
            toast.add({
                severity: 'error',
                summary: 'Gagal Cek MinIO / S3',
                detail: err.response?.data?.message || 'Terjadi kesalahan server saat tes koneksi S3.',
                life: 5000
            });
        })
        .finally(() => {
            checkingS3.value = false;
        });
};

// Progres Backup & Restore State
const displayProgressModal = ref(false);
const progressValue = ref(0);
const progressMessage = ref('Menyiapkan proses backup...');
const progressTitle = ref('Proses Backup Sedang Berlangsung');
const progressMode = ref('backup'); // 'backup' | 'restore'
let progressInterval = null;

const octaneStatus = ref({ is_octane: false, server: 'Menunggu...' });

onMounted(() => {
    axios.get('/octane-status')
        .then(res => {
            if(res.data) {
                octaneStatus.value = res.data;
            }
        })
        .catch(err => console.error("Gagal mengecek status octane", err));
});

const runBackup = (onlyLocal = false) => {
    progressMode.value = 'backup';
    progressTitle.value = onlyLocal ? 'Proses Backup Lokal (Tanpa S3)' : 'Proses Backup Sedang Berlangsung';
    progressValue.value = 0;
    progressMessage.value = onlyLocal ? 'Menyiapkan backup ke disk lokal...' : 'Menyiapkan proses backup...';
    displayProgressModal.value = true;
    loading.value = true;

    // Simulasikan bar progres berjalan dinamis
    progressInterval = setInterval(() => {
        if (progressValue.value < 90) {
            // Naikkan persentase secara acak
            progressValue.value += Math.floor(Math.random() * 6) + 3;
            if (progressValue.value > 90) progressValue.value = 90;
            
            // Perbarui pesan status sesuai tingkat progres
            if (progressValue.value < 25) {
                progressMessage.value = 'Mengekspor data database (pg_dump)...';
            } else if (progressValue.value < 60) {
                progressMessage.value = 'Mengumpulkan berkas-berkas fisik (non-build)...';
            } else if (progressValue.value < 85) {
                progressMessage.value = 'Mengompres dan membuat file arsip ZIP...';
            } else {
                progressMessage.value = onlyLocal 
                    ? 'Menyimpan berkas cadangan ke disk lokal...' 
                    : 'Menyimpan berkas cadangan ke dalam server...';
            }
        }
    }, 1000);

    router.post(route('admin.backups.run'), { only_local: onlyLocal }, {
        onSuccess: () => {
            clearInterval(progressInterval);
            progressValue.value = 100;
            progressMessage.value = onlyLocal ? 'Backup lokal berhasil dibuat!' : 'Backup berhasil dibuat!';
            
            setTimeout(() => {
                displayProgressModal.value = false;
                loading.value = false;
            }, 1000);
        },
        onError: () => {
            clearInterval(progressInterval);
            displayProgressModal.value = false;
            loading.value = false;
        },
        onFinish: () => {
            if (progressInterval) clearInterval(progressInterval);
        }
    });
};

const downloadFile = (fileName) => {
    window.location.href = route('admin.backups.download', fileName);
};

const syncToS3 = (fileName) => {
    confirm.require({
        message: `Apakah Anda yakin ingin mengirim file backup "${fileName}" ke S3 Minio? Proses ini mungkin memakan waktu tergantung ukuran file.`,
        header: 'Konfirmasi Sync S3',
        icon: 'pi pi-cloud-upload',
        acceptClass: 'p-button-info',
        accept: () => {
            loading.value = true;
            router.post(route('admin.backups.sync-s3', fileName), {}, {
                onSuccess: () => {
                    loading.value = false;
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Backup berhasil dikirim ke S3 Minio' });
                },
                onError: () => {
                    loading.value = false;
                    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal mengirim backup ke S3 Minio' });
                }
            });
        }
    });
};

const confirmDelete = (fileName) => {
    confirm.require({
        message: `Apakah Bapak yakin ingin menghapus file "${fileName}"? Data ini tidak dapat dikembalikan.`,
        header: 'Konfirmasi Penghapusan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.backups.destroy', fileName), {
                onSuccess: () => {
                    
                }
            });
        }
    });
};
const startRestoreProgress = () => {
    progressMode.value = 'restore';
    progressTitle.value = 'Proses Restore Sedang Berlangsung';
    progressValue.value = 0;
    progressMessage.value = 'Menyiapkan proses restore...';
    displayProgressModal.value = true;
    loading.value = true;

    // Tahapan pesan restore sesuai alur nyata di BackupController
    const stages = [
        { threshold: 10, msg: 'Mengaktifkan mode maintenance (artisan down)...' },
        { threshold: 25, msg: 'Membuat folder temporary & membuka arsip ZIP...' },
        { threshold: 45, msg: 'Mengekstrak isi ZIP ke folder sementara...' },
        { threshold: 60, msg: 'Mencari file dump database (.sql / .sql.gz)...' },
        { threshold: 72, msg: 'Mendekompresi file .sql.gz...' },
        { threshold: 85, msg: 'Mengeksekusi restore database via psql...' },
        { threshold: 95, msg: 'Memulihkan berkas fisik (storage/app/public)...' },
    ];

    progressInterval = setInterval(() => {
        if (progressValue.value < 95) {
            progressValue.value += Math.floor(Math.random() * 4) + 1;
            if (progressValue.value > 95) progressValue.value = 95;

            const stage = stages.slice().reverse().find(s => progressValue.value >= s.threshold);
            if (stage) progressMessage.value = stage.msg;
        }
    }, 1200);
};

// State Modal Peringatan Bahaya Produksi
const displayProdRestoreModal = ref(false);
const prodRestoreTarget = ref(null);
const prodConfirmInput = ref('');
const prodConfirmError = ref('');

const openProdRestoreModal = (target) => {
    prodRestoreTarget.value = target;
    prodConfirmInput.value = '';
    prodConfirmError.value = '';
    displayProdRestoreModal.value = true;
};

const closeProdRestoreModal = () => {
    displayProdRestoreModal.value = false;
    prodConfirmInput.value = '';
    prodConfirmError.value = '';
};

const executeProdRestore = () => {
    const expected = (props.db_name || 'portal_db').trim().toLowerCase();
    const input = prodConfirmInput.value.trim().toLowerCase();

    if (input !== expected && input !== 'restore_produksi') {
        prodConfirmError.value = `Nama database tidak cocok! Ketik tepat: ${props.db_name || 'portal_db'}`;
        return;
    }

    prodConfirmError.value = '';
    displayProdRestoreModal.value = false;

    if (prodRestoreTarget.value?.type === 'file') {
        startRestoreProgress();
        router.post(route('admin.backups.restore', prodRestoreTarget.value.fileName), {
            confirm_db: prodConfirmInput.value.trim()
        }, {
            onSuccess: () => {
                clearInterval(progressInterval);
                progressValue.value = 100;
                progressMessage.value = 'Database & berkas berhasil dipulihkan di Server Produksi!';
                setTimeout(() => {
                    displayProgressModal.value = false;
                    loading.value = false;
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Database produksi berhasil dipulihkan' });
                }, 1500);
            },
            onError: () => {
                clearInterval(progressInterval);
                displayProgressModal.value = false;
                loading.value = false;
            },
            onFinish: () => {
                if (progressInterval) clearInterval(progressInterval);
            }
        });
    } else if (prodRestoreTarget.value?.type === 'upload') {
        startRestoreProgress();
        uploadForm.transform((data) => ({
            ...data,
            confirm_db: prodConfirmInput.value.trim()
        })).post(route('admin.backups.restore-upload'), {
            onSuccess: () => {
                clearInterval(progressInterval);
                progressValue.value = 100;
                progressMessage.value = 'Database & berkas berhasil dipulihkan di Server Produksi!';
                setTimeout(() => {
                    displayProgressModal.value = false;
                    loading.value = false;
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Database produksi berhasil dipulihkan via upload' });
                }, 1500);
            },
            onError: () => {
                clearInterval(progressInterval);
                displayProgressModal.value = false;
                loading.value = false;
            },
            onFinish: () => {
                if (progressInterval) clearInterval(progressInterval);
            }
        });
    }
};

const confirmRestore = (fileName) => {
    lastAttemptedRestore.value = { type: 'file', fileName };

    if (props.is_production) {
        // SERVER PRODUKSI: Terangkan rincian bahaya & minta konfirmasi nama database
        openProdRestoreModal({ type: 'file', fileName });
    } else {
        // STAGING / DEVELOPMENT / LOKAL: Cukup notifikasi konfirmasi biasa
        confirm.require({
            message: `Apakah Anda ingin merestore database & berkas dari file "${fileName}"?`,
            header: 'Konfirmasi Restore Database',
            icon: 'pi pi-refresh',
            acceptClass: 'p-button-warning',
            accept: () => {
                startRestoreProgress();
                router.post(route('admin.backups.restore', fileName), {}, {
                    onSuccess: () => {
                        clearInterval(progressInterval);
                        progressValue.value = 100;
                        progressMessage.value = 'Database & berkas berhasil dipulihkan!';
                        setTimeout(() => {
                            displayProgressModal.value = false;
                            loading.value = false;
                            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Database berhasil dipulihkan' });
                        }, 1500);
                    },
                    onError: () => {
                        clearInterval(progressInterval);
                        displayProgressModal.value = false;
                        loading.value = false;
                    },
                    onFinish: () => {
                        if (progressInterval) clearInterval(progressInterval);
                    }
                });
            }
        });
    }
};

const forceUploadRestore = ref(false);
const uploadForm = useForm({
    backup_file: null,
    challenge_keyword: '',
    confirm_db: '',
});

const onFileSelect = (event) => {
    uploadForm.backup_file = event.target.files[0];
};

const submitUploadRestore = () => {
    if (forceUploadRestore.value && uploadForm.challenge_keyword.trim().toUpperCase() !== 'FORCE_RESTORE') {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Keyword challenge salah! Ketik tepat: FORCE_RESTORE' });
        return;
    }

    lastAttemptedRestore.value = { type: 'upload' };
    displayUploadModal.value = false;

    if (props.is_production) {
        // SERVER PRODUKSI: Terangkan rincian bahaya & minta konfirmasi nama database
        openProdRestoreModal({ type: 'upload' });
    } else {
        // STAGING / DEVELOPMENT / LOKAL: Cukup notifikasi konfirmasi biasa
        confirm.require({
            message: 'Apakah Anda ingin merestore database dari berkas upload ini?',
            header: 'Konfirmasi Restore File Upload',
            icon: 'pi pi-upload',
            acceptClass: 'p-button-danger',
            accept: () => {
                startRestoreProgress();
                uploadForm.post(route('admin.backups.restore-upload'), {
                    onSuccess: () => {
                        clearInterval(progressInterval);
                        progressValue.value = 100;
                        progressMessage.value = 'Database & berkas berhasil dipulihkan!';
                        setTimeout(() => {
                            displayProgressModal.value = false;
                            loading.value = false;
                            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Database berhasil dipulihkan' });
                        }, 1500);
                    },
                    onError: () => {
                        clearInterval(progressInterval);
                        displayProgressModal.value = false;
                        loading.value = false;
                    },
                    onFinish: () => {
                        if (progressInterval) clearInterval(progressInterval);
                    }
                });
            }
        });
    }
};

const page = usePage();
const lastAttemptedRestore = ref(null);
const displayForceRestoreModal = ref(false);
const forceChallengeKeyword = ref('');
const forceChallengeError = ref('');
const forceRestoreTarget = ref(null);

const openForceRestoreModal = (target) => {
    forceRestoreTarget.value = target;
    forceChallengeKeyword.value = '';
    forceChallengeError.value = '';
    displayForceRestoreModal.value = true;
};

const closeForceRestoreModal = () => {
    displayForceRestoreModal.value = false;
    forceChallengeKeyword.value = '';
    forceChallengeError.value = '';
};

const executeForceRestore = () => {
    const input = forceChallengeKeyword.value.trim().toUpperCase();
    if (input !== 'FORCE_RESTORE') {
        forceChallengeError.value = 'Keyword challenge salah! Ketik tepat: FORCE_RESTORE';
        return;
    }
    forceChallengeError.value = '';
    displayForceRestoreModal.value = false;

    if (forceRestoreTarget.value?.type === 'file') {
        startRestoreProgress();
        router.post(route('admin.backups.restore', forceRestoreTarget.value.fileName), {
            challenge_keyword: 'FORCE_RESTORE'
        }, {
            onSuccess: () => {
                clearInterval(progressInterval);
                progressValue.value = 100;
                progressMessage.value = 'Database & berkas berhasil dipulihkan (Force Restore)!';
                setTimeout(() => {
                    displayProgressModal.value = false;
                    loading.value = false;
                }, 1500);
            },
            onError: () => {
                clearInterval(progressInterval);
                displayProgressModal.value = false;
                loading.value = false;
            },
            onFinish: () => {
                if (progressInterval) clearInterval(progressInterval);
            }
        });
    } else if (forceRestoreTarget.value?.type === 'upload') {
        startRestoreProgress();
        uploadForm.transform((data) => ({
            ...data,
            challenge_keyword: 'FORCE_RESTORE'
        })).post(route('admin.backups.restore-upload'), {
            onSuccess: () => {
                clearInterval(progressInterval);
                progressValue.value = 100;
                progressMessage.value = 'Database & berkas berhasil dipulihkan (Force Restore)!';
                setTimeout(() => {
                    displayProgressModal.value = false;
                    loading.value = false;
                    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Database berhasil dipulihkan via Force Restore' });
                }, 1500);
            },
            onError: () => {
                clearInterval(progressInterval);
                displayProgressModal.value = false;
                loading.value = false;
            },
            onFinish: () => {
                if (progressInterval) clearInterval(progressInterval);
            }
        });
    }
};

watch(() => page.props.flash, (flash) => {
    if (flash?.error && (flash.error.includes('FORCE_RESTORE') || flash.error.includes('Versi database') || flash.error.includes('manifest.json'))) {
        if (lastAttemptedRestore.value) {
            openForceRestoreModal(lastAttemptedRestore.value);
        }
    }
}, { deep: true });
</script>

<style scoped>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.spinner-rotate {
    border-top-color: var(--primary-color) !important;
    animation: spin 1.5s linear infinite;
}
</style>