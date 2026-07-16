import LoginRoute from "@/components/auth/LoginRoute";

export const metadata = { title: "Sign in — BlueWorx" };
export const dynamic = "force-dynamic"; // uses useSearchParams

export default function Page() {
  return <LoginRoute />;
}
