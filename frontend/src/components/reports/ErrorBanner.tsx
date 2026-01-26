import { Button } from "@/components/ui/button";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

type ErrorBannerProps = {
  message: string;
  onRetry: () => void;
};

const ErrorBanner = ({ message, onRetry }: ErrorBannerProps) => (
  <Alert variant="destructive">
    <AlertTitle>Wystąpił błąd</AlertTitle>
    <AlertDescription className="flex items-center justify-between gap-4">
      <span>{message}</span>
      <Button variant="destructive" size="sm" onClick={onRetry}>
        Spróbuj ponownie
      </Button>
    </AlertDescription>
  </Alert>
);

export default ErrorBanner;
