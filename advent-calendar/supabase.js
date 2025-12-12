import { createClient } from 'https://esm.sh/@supabase/supabase-js'

const supabaseUrl = 'https://szqwzslmaglrignthtfj.supabase.co'
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN6cXd6c2xtYWdscmlnbnRodGZqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDg1MzgxMjEsImV4cCI6MjA2NDExNDEyMX0.gHTwZFfI_vXzA0i6Di1btqV_ojOv8vcMJTzAuW_A6pI'

export const supabase = createClient(supabaseUrl, supabaseKey)
