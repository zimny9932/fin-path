import { Button } from '@/components/ui/button';

interface ErrorAlertProps {
  message: string;
  onRetry: () => void;
}

export const ErrorAlert = ({ message, onRetry }: ErrorAlertProps) => (
  <div className="rounded-lg border border-destructive bg-destructive/10 p-6 text-destructive">
    <div className="flex items-center justify-between gap-4">
      <div>
        <p className="font-semibold">Wystąpił błąd</p>
        <p className="text-sm">{message}</p>
      </div>
      <Button variant="destructive" onClick={onRetry}>
        Spróbuj ponownie
      </Button>
    </div>
  </div>
);

export default ErrorAlert;
