<template>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center">Вход в систему</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="handleLogin">
                            <div v-if="error" class="alert alert-danger" role="alert">
                                {{ error }}
                            </div>
                            <div class="mb-3">
                                <label for="login" class="form-label">Логин</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="login"
                                    v-model="form.login"
                                    required
                                    autofocus
                                />
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    v-model="form.password"
                                    required
                                />
                            </div>
                            <div class="mb-3 form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="remember"
                                    v-model="form.remember"
                                />
                                <label class="form-check-label" for="remember">
                                    Запомнить меня
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Войти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
const loading = ref(false);
const error = ref('');

const form = reactive({
    login: '',
    password: '',
    remember: false,
});

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/login', form);
        if (response.status === 200 || response.status === 302) {
            window.location.href = '/';
        }
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            error.value = err.response.data.errors.login
                ? err.response.data.errors.login[0]
                : 'Ошибка входа';
        } else {
            error.value = 'Неверный логин или пароль';
        }
    } finally {
        loading.value = false;
    }
};
</script>

