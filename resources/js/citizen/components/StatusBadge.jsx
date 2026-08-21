import { statusLabel, statusTone } from '../utils/applicationStatus';

export default function StatusBadge({ status }) {
    return (
        <span className={`capsule-lg ${statusTone(status)}`}>
            {statusLabel(status)}
        </span>
    );
}
