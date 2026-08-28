<template>
    <AppLayout>
        <div class="card">
            <div class="flex flex-column md:flex-row justify-content-between align-items-start md:align-items-center mb-4 gap-3 border-bottom-1 border-300 pb-3">
                <div>
                    <div class="text-500 text-sm mb-1">Manajemen Anggota Kelas</div>
                    <h2 class="text-3xl font-bold text-900 m-0">{{ classroom.name }}</h2>
                    <div class="flex align-items-center gap-3 mt-2">
                        <Tag severity="info" icon="pi pi-bookmark">
                            {{ classroom.academic_year?.name }} ({{ classroom.academic_year?.semester }})
                        </Tag>
                        <span class="text-600 flex align-items-center gap-1">
                            <i class="pi pi-user"></i> Wali Kelas: 
                            <span class="font-bold">{{ classroom.teacher?.full_name || 'Belum ditentukan' }}</span>
                        </span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <Link :href="route('admin.classrooms.index')">
                        <Button 
                        label="Kembali" 
                        icon="pi pi-arrow-left" 
                        severity="secondary" outlined 
                        v-tooltip.top=" 'Kembali ke Daftar Kelas' "
                        />
                    </Link>
                    <Button label="Tambah Anggota" icon="pi pi-user-plus" @click="openMemberModal" />
                </div>
            </div>

            <DataTable :value="students.data" stripedRows showGridlines>
                <template #empty> 
                    <div class="text-center p-4">Kelas ini belum memiliki siswa.</div> 
                </template>
                <Column header="No" style="width: 3rem">
                    <template #body="{ index }">
                        {{ (students.from || 1) + index }}
                    </template>
                </Column>

                <Column field="nis" header="NIS" style="width: 15%"></Column>
                
                <Column field="full_name" header="Nama Siswa">
                    <template #body="{ data }">
                        <span class="font-bold">{{ data.full_name }}</span>
                        <div class="text-xs text-500">{{ data.gender  ? 'Laki-laki' : 'Perempuan' }}</div>
                    </template>
                </Column>
                <Column header="Agama" style="width: 10%">
                    <template #body="{ data }">
                        {{ data.religion?.name || '-' }}
                    </template>
                </Column>
                <Column header="No. HP" field="phone"></Column>

                <Column header="Aksi" style="width: 10%">
                    <template #body="{ data }">
                        <Button 
                            icon="pi pi-sign-out" 
                            severity="danger" 
                            text 
                            rounded 
                            v-tooltip.top="'Keluarkan dari Kelas'" 
                            @click="confirmRemove(data)" 
                        />
                    </template>
                </Column>
            </DataTable>
            
            <div class="mt-4 flex justify-content-center" v-if="students.links">
                <template v-for="(link, k) in students.links" :key="k">
                    <Link 
                        v-if="link.url" 
                        :href="link.url" 
                        class="p-button p-component p-button-sm mx-1" 
                        :class="{'p-button-outlined': !link.active}"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <Dialog v-model:visible="displayMemberModal" header="Tambah Siswa Baru" :modal="true" :style="{ width: '500px' }">
            <div class="mb-3 text-sm text-600">
                Pilih siswa yang <b>belum memiliki kelas</b> pada tahun ajaran ini.
            </div>

            <form @submit.prevent="submitMember">
                <div class="field">
                    <label class="font-bold mb-2 block">Pilih Siswa</label>
                    <MultiSelect 
                        v-model="memberForm.student_ids" 
                        :options="availableStudents" 
                        optionLabel="full_name" 
                        optionValue="id" 
                        placeholder="Pilih Siswa..." 
                        display="chip" 
                        filter
                        class="w-full"
                    />
                    <small class="block mt-2 text-orange-500" v-if="availableStudents.length === 0">
                        Tidak ada siswa tersedia (semua sudah punya kelas).
                    </small>
                </div>

                <div class="flex justify-content-end gap-2 mt-4">
                    <Button label="Batal" severity="secondary" text @click="displayMemberModal = false" />
                    <Button label="Simpan" icon="pi pi-check" type="submit" :loading="memberForm.processing" :disabled="memberForm.student_ids.length === 0" />
                </div>
            </form>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';

// Components
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    classroom: Object,
    students: Object,
    religions: Array,
    activeYear: Object
});

const toast = useToast();
const confirm = useConfirm();
const displayMemberModal = ref(false);
const availableStudents = ref([]);

const memberForm = useForm({
    student_ids: []
});

// Buka Modal & Load Siswa yg belum punya kelas
const openMemberModal = async () => {
    memberForm.student_ids = [];
    try {
        const response = await axios.get(route('admin.students.available'));
        availableStudents.value = response.data;
        displayMemberModal.value = true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data siswa' });
    }
};

const submitMember = () => {
    memberForm.post(route('admin.classrooms.add-members', props.classroom.id), {
        onSuccess: () => {
            displayMemberModal.value = false;            
        }
    });
};

// Logic Keluarkan Siswa (KICK)
const confirmRemove = (student) => {
    confirm.require({
        message: `Keluarkan <b>${student.full_name}</b> dari kelas ini? <br><small class="text-red-500">Siswa akan menjadi "Tanpa Kelas" tapi data tidak terhapus.</small>`,
        header: 'Konfirmasi',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.classrooms.remove-member', [props.classroom.id, student.id]));
        }
    });
};
</script>