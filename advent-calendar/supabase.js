import { createClient } from 'https://esm.sh/@supabase/supabase-js'

const supabaseUrl = 'https://ufuftcbunxovmytqpvto.supabase.co'
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVmdWZ0Y2J1bnhvdm15dHFwdnRvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjU1NTM4OTEsImV4cCI6MjA4MTEyOTg5MX0.ul-4MxHDPpyUaVBcwqeMMjMWXMYBZxVNxQ9lgrPTqX0'

export const supabase = createClient(supabaseUrl, supabaseKey)
