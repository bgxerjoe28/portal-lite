<template>
    <AppLayout title="Proktoring Sesi Ujian">
        <div class="card">
            <!-- Header section -->
            <div class="flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex align-items-center gap-2">
                    <Link :href="route('cbt.proctor.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-900 m-0">Proktoring: {{ session.session?.name || '-' }}</h2>
                        <span class="text-500 block mt-1">
                            Ruangan: <b>{{ session.room?.name }}</b> | Tanggal: <b>{{ formatScheduleDate(session.date) }}</b>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <!-- Start / Finish exam buttons -->
                    <template v-if="sessionState.status === 'not_started'">
                        <Button 
                            v-if="attendanceSaved" 
                            label="Mulai Sesi Ujian" 
                            icon="pi pi-play" 
                            severity="success" 
                            @click="confirmStartExam" 
                        />
                    </template>
                    <Button 
                        v-if="sessionState.status === 'started'" 
                        label="Selesaikan Sesi (Tutup)" 
                        icon="pi pi-stop" 
                        severity="danger" 
                        @click="confirmEndExam" 
                    />
                    <template v-if="sessionState.status === 'ended'">
                        <Button 
                            label="Buka Kembali Sesi Ruangan" 
                            icon="pi pi-undo" 
                            severity="warning" 
                            @click="confirmRestartRoom" 
                        />
                        <span 
                            class="bg-green-100 text-green-800 font-bold px-3 py-2 border-round text-sm flex align-items-center gap-1"
                        >
                            <i class="pi pi-check-circle"></i> Sesi Selesai
                        </span>
                    </template>
                </div>
            </div>

            <!-- Controller row (Token & Info) -->
            <div class="grid mb-4">
                <!-- Token Generator -->
                <div class="col-12 md:col-6">
                    <div class="surface-card p-4 shadow-1 border-round border border-300 flex flex-column justify-content-between h-full bg-white">
                        <div>
                            <span class="text-500 font-bold uppercase text-xs tracking-wider block mb-2">Token Masuk Ujian</span>
                            <div v-if="sessionState.token" class="flex align-items-center gap-3">
                                <span class="text-4xl font-extrabold text-blue-600 bg-blue-50 px-3 py-2 border-round border border-blue-200 letter-spacing-2">
                                    {{ sessionState.token }}
                                </span>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">
                                        Token aktif (Berlaku 3 Menit)
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1 flex align-items-center gap-1">
                                        <i class="pi pi-clock"></i> Kedaluwarsa dalam: <b>{{ formatCountdown(sessionState.expires_in) }}</b>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-slate-400 py-3 font-semibold italic text-sm">
                                Token belum di-generate atau telah kedaluwarsa.
                            </div>
                        </div>

                        <div class="mt-4" v-if="sessionState.status === 'started'">
                            <Button 
                                :label="sessionState.token ? 'Generate Ulang Token' : 'Generate Token Baru'" 
                                icon="pi pi-key" 
                                severity="info" 
                                size="small"
                                class="w-max"
                                @click="generateToken"
                            />
                        </div>
                        <div class="mt-4 text-xs text-red-500 font-medium" v-else>
                            * Token hanya bisa di-generate jika status ujian sedang "Sedang Berlangsung".
                        </div>
                    </div>
                </div>

                <!-- Live Info -->
                <div class="col-12 md:col-6">
                    <div class="surface-card p-4 shadow-1 border-round border border-300 h-full bg-white flex flex-column justify-content-between">
                        <div>
                            <div class="flex justify-content-between align-items-center mb-3">
                                <span class="text-500 font-bold uppercase text-xs tracking-wider">Statistik Ruangan</span>
                                <span class="text-xs text-blue-600 font-bold"><i class="pi pi-users mr-1"></i>{{ studentStats.total }} Peserta Terdaftar</span>
                            </div>
                            <div class="grid text-center">
                                <div class="col-3 border-right border-200">
                                    <span class="text-xs text-500 block mb-1">Belum Mulai</span>
                                    <span class="text-xl font-extrabold text-slate-500">{{ studentStats.idle }}</span>
                                </div>
                                <div class="col-3 border-right border-200">
                                    <span class="text-xs text-500 block mb-1">Mengerjakan</span>
                                    <span class="text-xl font-extrabold text-amber-600">{{ studentStats.working }}</span>
                                </div>
                                <div class="col-3 border-right border-200">
                                    <span class="text-xs text-500 block mb-1">Suspend</span>
                                    <span class="text-xl font-extrabold text-orange-600">{{ studentStats.kicked }}</span>
                                </div>
                                <div class="col-3">
                                    <span class="text-xs text-500 block mb-1">Total Selesai</span>
                                    <span class="text-xl font-extrabold text-green-600">{{ studentStats.finished }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Selesai Mandiri vs Selesai Paksa -->
                        <div class="mt-3 pt-2 border-top border-200 flex justify-content-between text-xs text-600">
                            <span>Selesai Mandiri: <b class="text-green-700">{{ studentStats.finishedNormal }}</b></span>
                            <span>Selesai Sistem / Paksa: <b class="text-teal-700">{{ studentStats.finishedForced }}</b></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Section for 'not_started' status -->
            <div v-if="sessionState.status === 'not_started'" class="surface-card p-4 shadow-2 border-round mb-4">
                <div v-if="!attendanceSaved">
                    <h3 class="mt-0 mb-3 text-xl"><i class="pi pi-check-square mr-2 text-primary"></i>Presensi Peserta Ujian</h3>
                    <p class="text-600 mb-4">Silakan centang peserta yang <b>Hadir</b>. Peserta yang tidak dicentang (absen) tidak akan bisa mengikuti sesi ujian ini. Anda dapat mencentang semua peserta sekaligus.</p>
                    
                    <DataTable v-bind="$pagination({ label: 'assignedstudents' })" :value="assignedStudents" v-model:selection="selectedStudents" dataKey="id" responsiveLayout="scroll" class="p-datatable-sm" stripedRows>
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <Column header="No." headerStyle="width: 3rem">
                            <template #body="slotProps">
                                {{ slotProps.index + 1 }}
                            </template>
                        </Column>
                        <Column field="nisn" header="NISN" headerStyle="width: 15%"></Column>
                        <Column field="name" header="Nama Lengkap" sortable></Column>
                        <Column field="classroom_name" header="Kelas" sortable headerStyle="width: 20%"></Column>
                        <Column header="Bangku" headerStyle="width: 10%">
                            <template #body="slotProps">
                                <Tag severity="info" :value="slotProps.data.seat_number"></Tag>
                            </template>
                        </Column>
                    </DataTable>
                    
                    <div class="mt-4 flex justify-content-end align-items-center gap-2">
                        <span class="mr-2 text-600 font-semibold">Terpilih: {{ selectedStudents.length }} dari {{ assignedStudents.length }} Siswa</span>
                        <Button label="Batal" icon="pi pi-times" severity="secondary" outlined @click="cancelEditAttendance" />
                        <Button label="Simpan Presensi" icon="pi pi-save" severity="primary" @click="saveAttendance" :loading="savingAttendance" />
                    </div>
                </div>
                <div v-else class="flex align-items-center justify-content-between p-3 bg-blue-50 border-round border border-blue-200">
                    <div class="flex align-items-center gap-3">
                        <div class="w-3rem h-3rem border-round-circle bg-blue-500 text-white flex align-items-center justify-content-center text-xl shadow-1">
                            <i class="pi pi-check"></i>
                        </div>
                        <div>
                            <h3 class="mt-0 mb-1 text-blue-900">Absensi Telah Disimpan</h3>
                            <p class="text-blue-700 m-0">Jumlah peserta siap ujian: <b>{{ sessionState.present_students?.length || 0 }}</b> siswa dari <b>{{ assignedStudents.length }}</b> total siswa terdaftar.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Edit Absensi" icon="pi pi-pencil" severity="secondary" outlined @click="editAttendance" />
                        <Button label="Mulai Sesi Ujian" icon="pi pi-play" severity="success" @click="confirmStartExam" />
                    </div>
                </div>
            </div>

            <!-- Seat Legend -->
            <div v-if="sessionState.status !== 'not_started'" class="flex gap-4 align-items-center mb-4 p-3 bg-gray-50 border-round border border-200 text-xs flex-wrap">
                <span class="font-bold text-700">Keterangan Status:</span>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-gray-100 border border-200"></span>
                    <span class="text-500">Belum Login</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-blue-100 border border-blue-300"></span>
                    <span class="text-blue-700 font-semibold">Login (Menunggu)</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-amber-100 border border-amber-300"></span>
                    <span class="text-amber-700 font-semibold">Mengerjakan</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-red-100 border border-red-300"></span>
                    <span class="text-red-700 font-semibold">Suspensi (Strike 1 & 2)</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-pink-100 border border-pink-300"></span>
                    <span class="text-pink-700 font-semibold">Logged Out (Strike 3)</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-green-100 border border-green-300"></span>
                    <span class="text-green-700 font-semibold">Selesai Mandiri</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-teal-100 border border-teal-300"></span>
                    <span class="text-teal-700 font-semibold">Selesai Sistem (Waktu/Pengawas)</span>
                </div>
                <div class="flex align-items-center gap-1">
                    <span class="w-1rem h-1rem border-round bg-red-800 border border-red-900"></span>
                    <span class="text-red-900 font-semibold">Selesai Paksa (Strike 4)</span>
                </div>
            </div>

            <!-- View Mode Switcher (Denah Kotak vs Tabel) -->
            <div v-if="sessionState.status !== 'not_started'" class="flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="flex align-items-center gap-2">
                    <span class="text-sm font-bold text-700">Tampilan Monitor:</span>
                    <div class="flex p-1 bg-gray-200 border-round gap-1">
                        <button 
                            type="button" 
                            class="px-3 py-1.5 border-none border-round text-xs font-bold cursor-pointer flex align-items-center gap-1.5 transition-all"
                            :class="viewMode === 'grid' ? 'bg-white text-primary shadow-1' : 'bg-transparent text-600 hover:text-900'"
                            @click="setViewMode('grid')"
                        >
                            <i class="pi pi-th-large"></i>
                            <span>Denah Kotak (Card)</span>
                        </button>
                        <button 
                            type="button" 
                            class="px-3 py-1.5 border-none border-round text-xs font-bold cursor-pointer flex align-items-center gap-1.5 transition-all"
                            :class="viewMode === 'table' ? 'bg-white text-primary shadow-1' : 'bg-transparent text-600 hover:text-900'"
                            @click="setViewMode('table')"
                        >
                            <i class="pi pi-list"></i>
                            <span>Tabel Peserta ({{ tableStudents.length }})</span>
                        </button>
                    </div>
                </div>

                <div v-if="viewMode === 'table'" class="flex align-items-center gap-2">
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon bg-white">
                            <i class="pi pi-search text-500"></i>
                        </span>
                        <InputText v-model="tableSearch" placeholder="Cari nama, NISN, kelas..." class="p-inputtext-sm" />
                    </div>
                </div>
            </div>

            <!-- Seat Layout Seating Map (Card Grid View) -->
            <div v-if="sessionState.status !== 'not_started' && viewMode === 'grid'" class="surface-card p-4 shadow-2 border-round">
                <div class="grid-layout">
                    <div 
                        v-for="seatNum in 36" 
                        :key="seatNum" 
                        class="seat-box border-round border p-3 flex flex-column justify-content-between relative"
                        :class="getSeatClass(seats[seatNum])"
                    >
                        <!-- Top Row: Seat Badge & Warnings -->
                        <div class="flex justify-content-between align-items-center w-full">
                            <span class="seat-badge text-xs font-bold px-2 py-0.5 border-round" :class="getSeatBadgeClass(seats[seatNum])">
                                Kursi {{ seatNum }}
                            </span>
                            
                            <!-- Warning Warning/Exclamation Indicators -->
                            <div class="flex gap-1" v-if="seats[seatNum]?.student">
                                <span 
                                    v-if="seats[seatNum].warning_count > 0" 
                                    class="w-1.5rem h-1.5rem border-round-circle flex align-items-center justify-content-center bg-red-600 text-white font-bold text-xs"
                                    v-tooltip.top="'Warning: ' + seats[seatNum].warning_count + ' Kali'"
                                >
                                    ! {{ seats[seatNum].warning_count }}
                                </span>
                            </div>
                        </div>

                        <!-- Center: Student info / Empty -->
                        <div class="my-2 flex-grow-1 flex flex-column justify-content-center overflow-hidden w-full">
                            <template v-if="seats[seatNum] && seats[seatNum].status !== 'empty'">
                                <div 
                                    class="font-bold text-xs student-name-clamp" 
                                    :title="seats[seatNum].student.name"
                                >
                                    {{ seats[seatNum].student.name }}
                                </div>
                                <div class="text-xxs text-600 mt-1 overflow-hidden text-ellipsis whitespace-nowrap" :title="'NISN: ' + seats[seatNum].student.nisn + ' | Kelas: ' + seats[seatNum].student.classroom_name">
                                    NISN: {{ seats[seatNum].student.nisn }} | <b>{{ seats[seatNum].student.classroom_name }}</b>
                                </div>
                                
                                <!-- Suspension Timer display -->
                                <div v-if="seats[seatNum].status === 'suspensi'" class="mt-1 text-xxs font-bold text-red-700 flex align-items-center gap-1 overflow-hidden whitespace-nowrap text-ellipsis">
                                    <i class="pi pi-spin pi-spinner text-xs"></i> Suspensi: {{ formatCountdown(seats[seatNum].suspended_seconds) }}
                                </div>

                                <!-- Progress Soal Indicator -->
                                <div v-if="seats[seatNum].total_questions > 0 && ['started', 'suspensi', 'submitted', 'logged_out', 'blocked'].includes(seats[seatNum].status)" class="mt-2 w-full">
                                    <div class="flex justify-content-between align-items-center text-xxs font-bold mb-1" :class="seats[seatNum].answered_count === seats[seatNum].total_questions ? 'text-emerald-700' : 'text-slate-600'">
                                        <span class="overflow-hidden whitespace-nowrap text-ellipsis">Soal</span>
                                        <span class="ml-1 font-extrabold">{{ seats[seatNum].answered_count }}/{{ seats[seatNum].total_questions }}</span>
                                    </div>
                                    <div class="w-full bg-slate-200 border-round overflow-hidden" style="height: 5px;">
                                        <div 
                                            class="h-full border-round transition-all duration-300"
                                            :class="seats[seatNum].answered_count === seats[seatNum].total_questions ? 'bg-emerald-500' : 'bg-blue-500'"
                                            :style="{ width: Math.min(100, Math.round((seats[seatNum].answered_count / seats[seatNum].total_questions) * 100)) + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Submit Type Indicator -->
                                <div v-if="seats[seatNum].status === 'submitted'" class="mt-2">
                                    <span 
                                        v-if="seats[seatNum].submit_type === 'student' || (!seats[seatNum].submit_type && seats[seatNum].warning_count < 4 && !seats[seatNum].is_blocked)"
                                        class="text-xxs font-bold text-green-700 bg-green-100 px-1.5 py-0.5 border-round inline-flex align-items-center gap-1"
                                    >
                                        <i class="pi pi-check"></i> Selesai Mandiri
                                    </span>
                                    <span 
                                        v-else-if="seats[seatNum].submit_type === 'system_timeout'"
                                        class="text-xxs font-bold text-teal-800 bg-teal-100 px-1.5 py-0.5 border-round inline-flex align-items-center gap-1"
                                    >
                                        <i class="pi pi-clock"></i> Selesai (Waktu)
                                    </span>
                                    <span 
                                        v-else-if="seats[seatNum].submit_type === 'system_proctor'"
                                        class="text-xxs font-bold text-purple-800 bg-purple-100 px-1.5 py-0.5 border-round inline-flex align-items-center gap-1"
                                    >
                                        <i class="pi pi-desktop"></i> Selesai (Pengawas)
                                    </span>
                                    <span 
                                        v-else-if="seats[seatNum].submit_type === 'system_teacher'"
                                        class="text-xxs font-bold text-orange-800 bg-orange-100 px-1.5 py-0.5 border-round inline-flex align-items-center gap-1"
                                    >
                                        <i class="pi pi-bolt"></i> Selesai (Guru/Paksa)
                                    </span>
                                    <span 
                                        v-else
                                        class="text-xxs font-bold text-red-800 bg-red-100 px-1.5 py-0.5 border-round inline-flex align-items-center gap-1"
                                    >
                                        <i class="pi pi-ban"></i> Selesai (Ban/Sistem)
                                    </span>
                                </div>
                            </template>
                            <div v-else class="text-center text-400 py-3 italic text-xs font-semibold">
                                Kursi Kosong
                            </div>
                        </div>

                        <!-- Bottom: Action Context buttons (Force Logout & Reopen) -->
                        <div class="flex justify-content-end gap-1" v-if="seats[seatNum]?.student">
                            <Button 
                                v-if="['login', 'started', 'suspensi'].includes(seats[seatNum].status)"
                                icon="pi pi-sign-out" 
                                severity="danger" 
                                text 
                                raised
                                rounded 
                                size="small"
                                class="h-2rem w-2rem"
                                :disabled="sessionState.status === 'ended'"
                                v-tooltip.top="sessionState.status === 'ended' ? 'Sesi ruangan telah selesai' : 'Keluarkan Siswa Paksa'"
                                @click="confirmLogoutStudent(seats[seatNum].student)"
                            />
                            <Button 
                                v-if="['submitted', 'logged_out'].includes(seats[seatNum].status) && !seats[seatNum].is_blocked && seats[seatNum].warning_count < 4"
                                icon="pi pi-undo" 
                                severity="warning" 
                                text 
                                raised
                                rounded 
                                size="small"
                                class="h-2rem w-2rem"
                                :disabled="sessionState.status === 'ended'"
                                v-tooltip.top="sessionState.status === 'ended' ? 'Sesi ruangan telah selesai' : 'Reset Status Ujian'"
                                @click="confirmReopenStudent(seats[seatNum].student)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alternative Table View -->
            <div v-if="sessionState.status !== 'not_started' && viewMode === 'table'" class="surface-card p-4 shadow-2 border-round">
                <DataTable v-bind="$pagination({ label: 'filteredtablestudents' })" 
                    :value="filteredTableStudents" 
                    stripedRows 
                    tableStyle="min-width: 50rem"
                    class="p-datatable-sm"
                >
                    <template #empty> Tidak ada data siswa di ruangan ini. </template>

                    <Column header="No." headerStyle="width: 4rem" style="width: 4rem">
                        <template #body="slotProps">
                            <span class="font-bold text-slate-700">{{ slotProps.index + 1 }}</span>
                        </template>
                    </Column>

                    <Column header="Bangku" field="seat_number" sortable headerStyle="width: 6.5rem" style="width: 6.5rem">
                        <template #body="{ data }">
                            <Tag severity="info" :value="`Kursi ${data.seat_number}`" class="font-mono text-xs font-bold" />
                        </template>
                    </Column>

                    <Column field="student.name" header="Nama Siswa" sortable headerStyle="min-width: 14rem">
                        <template #body="{ data }">
                            <span class="font-bold text-900">{{ data.student?.name || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="student.nisn" header="NISN" sortable headerStyle="width: 11rem" style="width: 11rem">
                        <template #body="{ data }">
                            <span class="font-mono text-slate-700 text-sm">{{ data.student?.nisn || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="student.classroom_name" header="Kelas" sortable headerStyle="width: 10rem" style="width: 10rem">
                        <template #body="{ data }">
                            <Tag :value="data.student?.classroom_name || '-'" severity="secondary" />
                        </template>
                    </Column>

                    <Column header="Status Pengerjaan" sortable field="status" headerStyle="min-width: 14rem">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <div class="flex align-items-center gap-1.5">
                                    <Tag 
                                        :value="getTableStatusLabel(data)" 
                                        :severity="getTableStatusSeverity(data)" 
                                    />
                                    <span v-if="data.status === 'suspensi'" class="text-xs font-bold text-red-600 flex align-items-center gap-1">
                                        <i class="pi pi-spin pi-spinner text-xs"></i> {{ formatCountdown(data.suspended_seconds) }}
                                    </span>
                                </div>

                                <!-- Progress Soal Indicator -->
                                <div v-if="data.total_questions > 0 && ['started', 'suspensi', 'submitted', 'logged_out', 'blocked'].includes(data.status)" class="mt-1" style="max-width: 150px;">
                                    <div class="flex justify-content-between text-xxs font-semibold text-slate-700 mb-0.5">
                                        <span>Soal:</span>
                                        <b class="text-blue-600 font-mono">{{ data.answered_count }}/{{ data.total_questions }}</b>
                                    </div>
                                    <div class="w-full bg-slate-200 border-round overflow-hidden" style="height: 5px;">
                                        <div 
                                            class="h-full border-round transition-all duration-300"
                                            :class="data.answered_count === data.total_questions ? 'bg-green-500' : 'bg-blue-500'"
                                            :style="{ width: Math.min(100, Math.round((data.answered_count / data.total_questions) * 100)) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Status Pelanggaran" sortable field="warning_count" headerStyle="width: 13rem" style="width: 13rem">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <div class="flex align-items-center gap-1">
                                    <Tag 
                                        :value="data.warning_count > 0 ? `${data.warning_count}x Pelanggaran` : '0x (Tertib)'" 
                                        :severity="data.warning_count >= 4 ? 'danger' : data.warning_count >= 3 ? 'danger' : data.warning_count > 0 ? 'warn' : 'success'" 
                                    />
                                </div>
                                <small v-if="data.warning_count === 1" class="text-xs text-yellow-700">Lock Layar 1 Mnt</small>
                                <small v-else-if="data.warning_count === 2" class="text-xs text-orange-700">Lock Layar 5 Mnt (Suspensi)</small>
                                <small v-else-if="data.warning_count === 3" class="text-xs text-red-600">Dikeluarkan dari Ujian</small>
                                <small v-else-if="data.warning_count >= 4" class="text-xs text-red-800 font-bold">Diblokir Permanen (Ban)</small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Aksi" headerStyle="width: 8rem" style="width: 8rem">
                        <template #body="{ data }">
                            <div class="flex gap-1" v-if="data.student">
                                <Button 
                                    v-if="['login', 'started', 'suspensi'].includes(data.status)"
                                    icon="pi pi-sign-out" 
                                    severity="danger" 
                                    text 
                                    rounded 
                                    size="small"
                                    :disabled="sessionState.status === 'ended'"
                                    v-tooltip.top="sessionState.status === 'ended' ? 'Sesi ruangan telah selesai' : 'Keluarkan Siswa Paksa'"
                                    @click="confirmLogoutStudent(data.student)" 
                                />
                                <Button 
                                    v-if="['submitted', 'logged_out'].includes(data.status) && !data.is_blocked && data.warning_count < 4"
                                    icon="pi pi-undo" 
                                    severity="warning" 
                                    text 
                                    rounded 
                                    size="small"
                                    :disabled="sessionState.status === 'ended'"
                                    v-tooltip.top="sessionState.status === 'ended' ? 'Sesi ruangan telah selesai' : 'Reset Status Ujian'"
                                    @click="confirmReopenStudent(data.student)" 
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';

const props = defineProps({
    session: Object,
});

const confirm = useConfirm();
const toast = useToast();

const viewMode = ref(typeof window !== 'undefined' ? (localStorage.getItem('cbt_proctor_view_mode') || 'grid') : 'grid');

const setViewMode = (mode) => {
    viewMode.value = mode;
    if (typeof window !== 'undefined') {
        localStorage.setItem('cbt_proctor_view_mode', mode);
    }
};

const tableSearch = ref('');

const sessionState = ref({
    id: props.session.id,
    token: props.session.token,
    status: props.session.status,
    expires_in: 0,
    present_students: props.session.present_students || null,
});

const seats = ref({});
const selectedStudents = ref([]);
const savingAttendance = ref(false);
// Flag: mencegah polling menimpa form edit presensi yang sedang dibuka
const isEditingAttendance = ref(false);

const attendanceSaved = computed(() => {
    return sessionState.value.present_students !== null && sessionState.value.present_students !== undefined;
});

const assignedStudents = computed(() => {
    const students = [];
    Object.values(seats.value).forEach(seat => {
        if (seat.status !== 'empty' && seat.student) {
            students.push({
                ...seat.student,
                seat_number: seat.seat_number
            });
        }
    });
    return students.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'id'));
});

const tableStudents = computed(() => {
    const list = [];
    Object.values(seats.value).forEach(seat => {
        if (seat && seat.status !== 'empty' && seat.student) {
            list.push(seat);
        }
    });
    return list.sort((a, b) => a.seat_number - b.seat_number);
});

const filteredTableStudents = computed(() => {
    const query = (tableSearch.value || '').toLowerCase().trim();
    return tableStudents.value.filter(item => {
        if (!query) return true;
        const name = (item.student?.name || '').toLowerCase();
        const nisn = (item.student?.nisn || '').toLowerCase();
        const classroom = (item.student?.classroom_name || '').toLowerCase();
        const seat = (item.seat_number?.toString() || '');
        return name.includes(query) || nisn.includes(query) || classroom.includes(query) || seat.includes(query);
    });
});
let pollInterval = null;

const studentStats = computed(() => {
    let total = 0;
    let working = 0;
    let finished = 0;
    let finishedNormal = 0;
    let finishedForced = 0;
    let kicked = 0;
    let idle = 0;

    Object.values(seats.value).forEach(s => {
        if (s.status !== 'empty') {
            total++;
            if (s.status === 'started' || s.status === 'suspensi') {
                working++;
            } else if (s.status === 'submitted') {
                finished++;
                if (s.submit_type === 'student' || (!s.submit_type && s.warning_count < 4 && !s.is_blocked)) {
                    finishedNormal++;
                } else {
                    finishedForced++;
                }
            } else if (s.status === 'blocked') {
                finished++;
                finishedForced++;
            } else if (s.status === 'logged_out') {
                kicked++;
            } else if (s.status === 'not_started' || s.status === 'login') {
                idle++;
            }
        }
    });

    return { 
        total, 
        working, 
        finished, 
        finishedNormal, 
        finishedForced, 
        kicked, 
        idle 
    };
});

const fetchLiveStatus = async () => {
    try {
        const response = await axios.get(route('cbt.proctor.status', props.session.id));
        const newSession = response.data.session;

        // Jika sedang dalam mode edit presensi, JANGAN overwrite present_students
        // agar form edit tidak tertutup otomatis oleh polling 5 detik.
        if (isEditingAttendance.value) {
            // Update hanya data yang tidak merusak state edit (token, status, expires_in)
            sessionState.value.token = newSession.token;
            sessionState.value.status = newSession.status;
            sessionState.value.expires_in = newSession.expires_in ?? 0;
            // present_students dibiarkan null agar form tetap terbuka
        } else {
            sessionState.value = newSession;
        }

        seats.value = response.data.seats;
    } catch (e) {
        console.error('Failed to poll proctor status:', e);
    }
};

let countdownInterval = null;

const startCountdown = () => {
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (sessionState.value.expires_in > 0) {
            sessionState.value.expires_in--;
        }
        Object.values(seats.value).forEach(seat => {
            if (seat && seat.status === 'suspensi' && seat.suspended_seconds > 0) {
                seat.suspended_seconds--;
            }
        });
    }, 1000);
};

onMounted(() => {
    // Jalankan polling setiap 5 detik
    fetchLiveStatus();
    pollInterval = setInterval(fetchLiveStatus, 5000);
    startCountdown();
});

onUnmounted(() => {
    clearInterval(pollInterval);
    if (countdownInterval) clearInterval(countdownInterval);
});

const generateToken = () => {
    router.post(route('cbt.proctor.generate-token', props.session.id), {}, {
        onSuccess: () => {
            fetchLiveStatus();
        }
    });
};

const editAttendance = () => {
    // Populate selectedStudents based on present_students array
    if (sessionState.value.present_students) {
        selectedStudents.value = assignedStudents.value.filter(s => sessionState.value.present_students.includes(s.id));
    } else {
        selectedStudents.value = [];
    }
    // Set flag edit aktif — polling tidak akan menutup form ini
    isEditingAttendance.value = true;
    // Set null agar form ditampilkan (computed attendanceSaved = false)
    sessionState.value.present_students = null;
};

const cancelEditAttendance = () => {
    // Batalkan edit — refresh data dari server agar present_students kembali
    isEditingAttendance.value = false;
    fetchLiveStatus();
};

const saveAttendance = () => {
    savingAttendance.value = true;
    const presentIds = selectedStudents.value.map(s => s.id);
    
    router.post(route('cbt.proctor.attendance', props.session.id), {
        present_students: presentIds
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Absensi telah disimpan.', life: 3000 });
            // Matikan flag edit sebelum fetch agar polling bisa update kembali
            isEditingAttendance.value = false;
            fetchLiveStatus();
            savingAttendance.value = false;
        },
        onError: () => {
            savingAttendance.value = false;
        }
    });
};

const confirmStartExam = () => {
    confirm.require({
        message: 'Mulai ujian untuk ruangan ini? Siswa yang terdaftar baru bisa masuk setelah sesi dimulai.',
        header: 'Mulai Sesi Ujian',
        icon: 'pi pi-play',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('cbt.proctor.start', props.session.id), {}, {
                onSuccess: () => {
                    fetchLiveStatus();
                }
            });
        }
    });
};

const confirmEndExam = () => {
    confirm.require({
        message: 'Selesaikan Ujian? Peserta akan dilogout keluar, jawaban sudah tersimpan',
        header: 'Selesaikan Sesi Ujian',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('cbt.proctor.end', props.session.id), {}, {
                onSuccess: () => {
                    fetchLiveStatus();
                }
            });
        }
    });
};

const confirmRestartRoom = () => {
    confirm.require({
        message: 'Buka sesi ujian? Sesi ujian akan diaktifkan kembali dan jawaban siswa tersimpan.',
        header: 'Buka Sesi Ujian (Serentak)',
        icon: 'pi pi-undo',
        acceptClass: 'p-button-warning',
        acceptLabel: 'Ya, Buka Sesi Ujian',
        rejectLabel: 'Batal',
        accept: () => {
            router.post(route('cbt.proctor.restart-room', props.session.id), {}, {
                onSuccess: () => {
                    fetchLiveStatus();
                }
            });
        }
    });
};

const confirmLogoutStudent = (student) => {
    confirm.require({
        message: `Keluarkan siswa "${student.name}" secara paksa? Siswa harus meminta token baru dari pengawas jika ingin login kembali.`,
        header: 'Konfirmasi Logout Paksa',
        icon: 'pi pi-sign-out',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('cbt.proctor.logout-student', { id: props.session.id, student_id: student.id }), {}, {
                onSuccess: () => {
                    fetchLiveStatus();
                }
            });
        }
    });
};

const confirmReopenStudent = (student) => {
    confirm.require({
        message: `Buka sesi ujian untuk "${student.name}"? Kuncian akan dibuka dan jawaban sudah tersimpan.`,
        header: 'Reset Status Ujian',
        icon: 'pi pi-undo',
        acceptClass: 'p-button-warning',
        acceptLabel: 'Ya, Buka Kembali',
        rejectLabel: 'Batal',
        accept: () => {
            router.post(route('cbt.proctor.reopen-student', { id: props.session.id, student_id: student.id }), {}, {
                onSuccess: () => {
                    fetchLiveStatus();
                }
            });
        }
    });
};

const getSeatClass = (seat) => {
    if (!seat || seat.status === 'empty') return 'bg-slate-50 border-dashed border-200 text-300';
    
    switch (seat.status) {
        case 'login': // Waiting to click start
            return 'bg-blue-50 border-blue-300 text-blue-900';
        case 'started': // Working on test
            return 'bg-amber-50 border-amber-300 text-amber-900 shadow-sm';
        case 'suspensi': // Temporarily locked (warnings 1 and 2)
            return 'bg-red-50 border-red-400 text-red-900 font-bold border-2';
        case 'logged_out': // warning strike 3
            return 'bg-pink-50 border-pink-300 text-pink-900 border-2';
        case 'blocked': // warning strike 4 (perm block)
            return 'bg-red-900 border-red-950 text-white font-bold border-2';
        case 'submitted': // Finished
            if (seat.submit_type === 'student' || (!seat.submit_type && (seat.warning_count || 0) < 4 && !seat.is_blocked)) {
                return 'bg-green-50 border-green-300 text-green-900';
            }
            return 'bg-teal-50 border-teal-300 text-teal-900';
        default: // not_started
            return 'bg-slate-100 border-slate-300 text-slate-800';
    }
};

const getSeatBadgeClass = (seat) => {
    if (!seat || seat.status === 'empty') return 'bg-slate-200 text-400';
    
    switch (seat.status) {
        case 'login':
            return 'bg-blue-200 text-blue-800';
        case 'started':
            return 'bg-amber-200 text-amber-800';
        case 'suspensi':
            return 'bg-red-200 text-red-800';
        case 'logged_out':
            return 'bg-pink-200 text-pink-800';
        case 'blocked':
            return 'bg-red-950 text-red-200';
        case 'submitted':
            if (seat.submit_type === 'student' || (!seat.submit_type && (seat.warning_count || 0) < 4 && !seat.is_blocked)) {
                return 'bg-green-200 text-green-800';
            }
            return 'bg-teal-200 text-teal-800';
        default:
            return 'bg-slate-200 text-600';
    }
};

const formatCountdown = (totalSeconds) => {
    const roundedSeconds = Math.max(0, Math.floor(totalSeconds));
    if (roundedSeconds <= 0) return '00:00';
    const mins = Math.floor(roundedSeconds / 60).toString().padStart(2, '0');
    const secs = (roundedSeconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
};

const formatScheduleDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getTableStatusLabel = (data) => {
    if (!data) return '-';
    const status = typeof data === 'string' ? data : data.status;
    const submitType = typeof data === 'object' ? data.submit_type : null;
    const warningCount = typeof data === 'object' ? (data.warning_count || 0) : 0;
    const isBlocked = typeof data === 'object' ? !!data.is_blocked : false;

    if (status === 'submitted') {
        if (submitType === 'student' || (!submitType && warningCount < 4 && !isBlocked)) {
            return 'Selesai Mandiri';
        }
        if (submitType === 'system_timeout') {
            return 'Selesai Sistem (Waktu Habis)';
        }
        if (submitType === 'system_proctor') {
            return 'Selesai Sistem (Pengawas)';
        }
        if (submitType === 'system_teacher') {
            return 'Selesai Sistem (Dipaksa Guru)';
        }
        if (submitType === 'system_cheat' || warningCount >= 4 || isBlocked) {
            return 'Selesai Sistem (Pelanggaran/Ban)';
        }
        return 'Selesai Sistem';
    }

    const labels = {
        'not_started': 'Belum Login',
        'login':       'Login (Menunggu)',
        'started':     'Sedang Mengerjakan',
        'suspensi':    'Suspensi Sementara',
        'logged_out':  'Dikeluarkan (Strike 3)',
        'blocked':     'Selesai Paksa (Strike 4)',
    };
    return labels[status] || status;
};

const getTableStatusSeverity = (data) => {
    if (!data) return 'secondary';
    const status = typeof data === 'string' ? data : data.status;
    const submitType = typeof data === 'object' ? data.submit_type : null;
    const warningCount = typeof data === 'object' ? (data.warning_count || 0) : 0;
    const isBlocked = typeof data === 'object' ? !!data.is_blocked : false;

    if (status === 'submitted') {
        if (submitType === 'student' || (!submitType && warningCount < 4 && !isBlocked)) {
            return 'success';
        }
        if (submitType === 'system_timeout') {
            return 'info';
        }
        if (submitType === 'system_proctor') {
            return 'help';
        }
        if (submitType === 'system_teacher') {
            return 'warn';
        }
        if (submitType === 'system_cheat' || warningCount >= 4 || isBlocked) {
            return 'danger';
        }
        return 'contrast';
    }

    const severities = {
        'not_started': 'secondary',
        'login':       'info',
        'started':     'warn',
        'suspensi':    'danger',
        'logged_out':  'danger',
        'blocked':     'danger',
    };
    return severities[status] || 'secondary';
};
</script>

<style scoped>
.grid-layout {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.85rem;
}

.seat-box {
    min-height: 150px;
    border-width: 1px;
    overflow: hidden;
    max-width: 100%;
    box-sizing: border-box;
}

.student-name-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.2;
}

.text-xxs {
    font-size: 0.65rem;
}

.letter-spacing-2 {
    letter-spacing: 2px;
}

@media (max-width: 1024px) {
    .grid-layout {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .grid-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
