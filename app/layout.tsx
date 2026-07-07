import type { Metadata } from "next";
import { Sora } from "next/font/google";
import Nav from "@/components/Nav";
import Footer from "@/components/Footer";
import CtaBand from "@/components/CtaBand";
import "./globals.css";

const sora = Sora({
  subsets: ["latin"],
  variable: "--font-sora",
});

export const metadata: Metadata = {
  title: "BlueWorx — Digital Agency & Platform",
  description:
    "BlueWorx is the agency behind high-performing digital solutions: websites, platforms, and automations. Strategy, design, build, hosting, and ongoing support from one dedicated team.",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body className={sora.variable}>
        <Nav />
        <main>{children}</main>
        <CtaBand />
        <Footer />
      </body>
    </html>
  );
}
