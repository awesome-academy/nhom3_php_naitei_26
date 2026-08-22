import { statusLabel, statusTone } from '../utils/applicationStatus';
import { useLanguage } from '../i18n/LanguageContext';

export default function StatusBadge({ status }) {
    const { t } = useLanguage();

    return (
        <span className={`capsule-lg ${statusTone(status)}`}>
            {statusLabel(status, t)}
        </span>
    );
}
