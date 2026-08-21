import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { forgetCitizenSession, getApiError, rememberCitizenSession } from '../api/auth';
import { fetchCitizenProfile, updateCitizenProfile } from '../api/profile';
import Footer from '../components/Footer';
import FormField, { FieldError } from '../components/FormField';
import Header from '../components/Header';

const initialForm = {
    name: '',
    date_of_birth: '',
    phone: '',
    address: '',
};

export default function ProfilePage() {
    const navigate = useNavigate();
    const [profile, setProfile] = useState(null);
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [flash, setFlash] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        let isMounted = true;

        async function loadProfile() {
            try {
                const response = await fetchCitizenProfile();

                if (!isMounted) {
                    return;
                }

                rememberCitizenSession(response.data);
                setProfile(response.data);
                setForm({
                    name: response.data.name ?? '',
                    date_of_birth: response.data.date_of_birth ?? '',
                    phone: response.data.phone ?? '',
                    address: response.data.address ?? '',
                });
            } catch {
                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: {
                        flash: 'Vui lòng đăng nhập để xem hồ sơ.',
                    },
                });
            } finally {
                if (isMounted) {
                    setIsLoading(false);
                }
            }
        }

        loadProfile();

        return () => {
            isMounted = false;
        };
    }, [navigate]);

    useEffect(() => {
        if (!flash) {
            return undefined;
        }

        const timeout = window.setTimeout(() => setFlash(''), 4000);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    function updateField(event) {
        setForm((current) => ({
            ...current,
            [event.target.name]: event.target.value,
        }));
    }

    async function submitForm(event) {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setFlash('');
        setIsSubmitting(true);

        try {
            const response = await updateCitizenProfile(form);

            setProfile(response.data);
            setFlash('Cập nhật hồ sơ thành công.');
        } catch (error) {
            const apiError = getApiError(error);
            setMessage(apiError.message);
            setErrors(apiError.errors);
        } finally {
            setIsSubmitting(false);
        }
    }

    if (isLoading) {
        return (
            <main className="min-h-screen bg-surface flex flex-col text-gray-900">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex items-center justify-center">
                    <p className="rounded-lg border border-border bg-white px-5 py-4 text-sm font-semibold text-gray-600">Đang tải hồ sơ...</p>
                </div>
                <Footer />
            </main>
        );
    }

    return (
        <main className="min-h-screen bg-surface flex flex-col text-gray-900">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <section className="px-10 py-8">
                    <div className="mb-6">
                        <p className="text-sm font-semibold uppercase text-primary">Hồ sơ công dân</p>
                        <h1 className="mt-2 text-[26px] font-bold tracking-tight text-gray-900">Thông tin tài khoản</h1>
                    </div>

                    <div className="grid items-start gap-6 lg:grid-cols-[280px_1fr]">
                        <aside className="rounded-lg border border-border bg-blue-50 p-5">
                            <p className="text-sm font-semibold uppercase text-primary">Hồ sơ công dân</p>
                            <h1 className="mt-3 text-2xl font-bold leading-tight text-gray-950">
                                Thông tin tài khoản
                            </h1>
                            <dl className="mt-5 space-y-4 text-sm">
                                <div>
                                    <dt className="font-semibold text-gray-500">Email</dt>
                                    <dd className="mt-1 break-all text-gray-950">{profile.email}</dd>
                                </div>
                                <div>
                                    <dt className="font-semibold text-gray-500">Số CCCD</dt>
                                    <dd className="mt-1 text-gray-950">{profile.citizen_id}</dd>
                                </div>
                                <div>
                                    <dt className="font-semibold text-gray-500">Vai trò</dt>
                                    <dd className="mt-1 text-gray-950">Công dân</dd>
                                </div>
                            </dl>
                        </aside>

                        <form className="card-container rounded-lg p-5 shadow-sm sm:p-6" noValidate onSubmit={submitForm}>
                            <div className="grid gap-x-4 gap-y-4 md:grid-cols-2">
                                <FormField
                                    autoComplete="name"
                                    errors={errors.name}
                                    label="Họ và tên"
                                    name="name"
                                    onChange={updateField}
                                    value={form.name}
                                />
                                <FormField
                                    errors={errors.date_of_birth}
                                    label="Ngày sinh"
                                    name="date_of_birth"
                                    onChange={updateField}
                                    type="date"
                                    value={form.date_of_birth}
                                />
                                <FormField
                                    autoComplete="tel"
                                    errors={errors.phone}
                                    label="Số điện thoại"
                                    name="phone"
                                    onChange={updateField}
                                    value={form.phone}
                                />
                                <div className="md:col-span-2">
                                    <label className="label mb-1.5 normal-case tracking-normal" htmlFor="address">
                                        Địa chỉ
                                    </label>
                                    <textarea
                                        className={`input-field min-h-24 resize-y rounded-lg px-3.5 py-2.5 text-sm ${
                                            errors.address ? 'input-error' : ''
                                        }`}
                                        id="address"
                                        name="address"
                                        onChange={updateField}
                                        value={form.address}
                                    />
                                    <FieldError errors={errors.address} />
                                </div>
                            </div>

                            {flash && (
                                <p className="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                                    {flash}
                                </p>
                            )}

                            {message && (
                                <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                                    {message}
                                </p>
                            )}

                            <div className="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-gray-600">
                                    Email, CCCD và vai trò không thể tự thay đổi.
                                </p>
                                <button className="btn-primary rounded-full px-8 py-3 text-base" disabled={isSubmitting}>
                                    {isSubmitting ? 'Đang lưu...' : 'Lưu thay đổi'}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <Footer />
            </div>
        </main>
    );
}
