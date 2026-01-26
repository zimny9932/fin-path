import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const ReportsSkeleton = () => (
  <div className="grid gap-4 md:grid-cols-2">
    <Card>
      <CardHeader>
        <CardTitle>Udział wydatków</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="mx-auto h-56 w-56">
          <Skeleton className="h-full w-full rounded-full" />
        </div>
        <div className="space-y-2">
          {[...Array(3)].map((_, idx) => (
            <div key={idx} className="flex items-center gap-2">
              <Skeleton className="h-3 w-3 rounded-sm" />
              <Skeleton className="h-3 w-32" />
            </div>
          ))}
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Podział kategorii</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {[...Array(6)].map((_, idx) => (
          <Skeleton key={idx} className="h-6 w-full" />
        ))}
      </CardContent>
    </Card>
  </div>
);

export default ReportsSkeleton;
