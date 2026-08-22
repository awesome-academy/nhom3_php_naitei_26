import { useLanguage } from '../i18n/LanguageContext';

function StepCircle({ step, index, current }) {
    const done = index < current;
    const active = index === current;

    if (done) {
        return (
            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-success text-white">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" /></svg>
            </div>
        );
    }

    return (
        <div className={`flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold ${active ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'}`}>
            {index + 1}
        </div>
    );
}

export default function ApplySteps({ currentStep }) {
    const { t } = useLanguage();
    const steps = [t('apply.stepInformation'), t('apply.stepDocuments'), t('apply.stepReview')];

    return (
        <ol className="flex items-center justify-center gap-3">
            {steps.map((label, index) => (
                <li key={label} className="flex items-center gap-3">
                    <div className="flex items-center gap-2.5">
                        <StepCircle step={index} index={index} current={currentStep} />
                        <span className={`text-sm font-semibold ${index <= currentStep ? 'text-gray-900' : 'text-gray-400'}`}>
                            {label}
                        </span>
                    </div>
                    {index < steps.length - 1 && (
                        <div className={`h-0.5 w-10 rounded-full ${index < currentStep ? 'bg-success' : 'bg-gray-200'}`} />
                    )}
                </li>
            ))}
        </ol>
    );
}
