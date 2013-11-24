<?php
/*
 * Copyright (c) 2013 André Mekkawi <license@diskusagereports.com>
 * Version: @@SourceVersion
 *
 * LICENSE
 *
 * This source file is subject to the MIT license in the file LICENSE.txt.
 * The license is also available at http://diskusagereports.com/license.html
 */

class ScanReader {

	const DEBUG = false;

	/**
	 * @var Report
	 */
	protected $report;

	public function __construct(Report $report) {
		$this->report = $report;
	}

	public function read($filename) {

		// Attempt to open the file list.
		try {
			$stream = new FileStream($filename, 'r');
		}
		catch (IOException $e) {
			throw new ScanException(ScanException::FOPEN_FAIL);
		}

		$start = microtime(true);
		$report = $this->report;
		$iterator = new FileIterator($stream);
		$fileInfo = new FileInfo($report);

		$progressLastReport = time();
		$progressLastLines = 0;
		$progressLastBytes = 0;
		$progressLastOutFiles = 0;
		$progressLastOutSize = 0;

		foreach ($iterator as $lineNum => $line) {

			if (Logger::doLevel(Logger::LEVEL_NORMAL)) {
				if (time() - $progressLastReport >= 3) {
					$message = '';

					if ($iterator->length() !== null) {
						$progressPercent = floor($iterator->position() / $iterator->length() * 1000) / 10;
						$message .= sprintf('%4.1f', $progressPercent) . "%: ";
					}

					$message .= "Processed " . Util::FormatNumber($lineNum - $progressLastLines) . " lines from " . Util::FormatBytes($iterator->position() - $progressLastBytes) . ". Wrote " . Util::FormatBytes($this->report->outSize - $progressLastOutSize) . " to " . Util::FormatNumber($this->report->outFiles - $progressLastOutFiles) . " files.";
					$progressLastReport = time();
					$progressLastBytes = $iterator->position();
					$progressLastOutFiles = $report->outFiles;
					$progressLastLines = $lineNum;
					$progressLastOutSize = $report->outSize;

					Logger::log($message, Logger::LEVEL_NORMAL);
				}
			}

			// Ignore blank lines
			if (trim($line) == '')
				continue;

			try {
				$flag = substr($line, 0, 1);

				// Process the header.
				if ($flag == '#') {
					if (!$report->processHeader($line))
						throw new ScanException(ScanException::HEADER_EXCEPTION);
				}

				elseif ($flag == '!') {
					//$report->processError($line);
				}

				elseif ($flag == 'd') {
					$report->processDirInfo(new DirInfo($report, $line));
				}

				elseif ($flag == 'f' || $flag == '-') {
					$fileInfo->setFromLine($line);
					$report->processFileInfo($fileInfo);
				}
			}
			catch (LineException $e) {
				Logger::error("LineException on line $lineNum: " . $e->getMessage());
			}
		}

		$report->save();

		if (Logger::doLevel(Logger::LEVEL_NORMAL)) {
			Logger::log("Complete! Processed " . Util::FormatNumber($iterator->key()) . " lines from " . Util::FormatBytes($iterator->position()) . ". Wrote " . Util::FormatBytes($report->outSize) . " in " . Util::FormatNumber($report->outFiles) . " files. Took " . sprintf('%.1f', microtime(true) - $start) . " sec.", Logger::LEVEL_NORMAL);
		}

		$stream->close();
	}
}
