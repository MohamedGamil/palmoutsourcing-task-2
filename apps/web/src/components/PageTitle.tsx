export interface PageTitleProps {
  title: string;
  subtitle?: string;
  button?: boolean;
  buttonText?: string;
  buttonHandler?: () => void;
}

export default function PageTitle({ title, subtitle, button, buttonText, buttonHandler }: PageTitleProps) {
  return (
    <div className="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{title}</h1>
        {subtitle && (
          <p className="mt-2 text-gray-600 dark:text-gray-300">
            {subtitle}
          </p>
        )}
      </div>
      <div className="mt-4 sm:mt-0">
        {button && buttonText && buttonHandler && (
          <button
            onClick={buttonHandler}
            className="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg className="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
            </svg>
            {buttonText}
          </button>
        )}
      </div>
    </div>
  );
}