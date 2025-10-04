import type { Metadata } from "next";
import { Poppins, Geist_Mono } from "next/font/google";
import Navigation from "@/components/Navigation";
import Footer from "@/components/Footer";
import CSRFProvider from "@/components/CSRFProvider";
import "./globals.css";
import { APP_DESCRIPTION, APP_NAME } from "@/constants";

const poppins = Poppins({
  variable: "--font-poppins",
  subsets: ["latin"],
  weight: ["400", "600", "700"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: APP_NAME,
  description: APP_DESCRIPTION,
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        className={`${poppins.variable} ${geistMono.variable} antialiased min-h-screen flex flex-col`}
      >
        <CSRFProvider>
          <Navigation />
          <main className="flex-1">
            {children}
          </main>
          <Footer />
        </CSRFProvider>
      </body>
    </html>
  );
}
